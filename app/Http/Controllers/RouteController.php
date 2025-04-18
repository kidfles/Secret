<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RouteController extends Controller
{
    private const CACHE_TTL = 900;
    private const CACHE_KEY = 'routes';

    public function index()
    {
        // Debug: Count queries
        DB::enableQueryLog();
        
        // Try to retrieve from cache first
        $cacheKey = self::CACHE_KEY . '_index';
        
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);
            
            // Add query debugging info
            $queryLog = DB::getQueryLog();
            $queryCount = count($queryLog);
            $data['queryCount'] = $queryCount;
            $data['queryLog'] = $queryLog;
            $data['fromCache'] = true;
            
            return view('routes.index', $data);
        }
        
        $routes = Route::with(['locations' => function ($q) {
            $q->orderBy('route_location.order');
        }])->get();

        // Pre-calculate tile totals and stats for each route to avoid N+1 queries
        $routeStats = [];
        $totalTilesAll = 0;
        
        foreach ($routes as $route) {
            $routeTiles = 0;
            $tilesByType = [];
            
            foreach ($route->locations as $location) {
                if ($location->tegels_count > 0) {
                    $routeTiles += $location->tegels_count;
                    $type = $location->tegels_type ?? 'onbekend';
                    
                    if (!isset($tilesByType[$type])) {
                        $tilesByType[$type] = 0;
                    }
                    
                    $tilesByType[$type] += $location->tegels_count;
                }
            }
            
            $totalTilesAll += $routeTiles;
            
            $routeStats[$route->id] = [
                'total_tiles' => $routeTiles,
                'tiles_by_type' => $tilesByType,
                'location_count' => $route->locations->count()
            ];
        }
        
        // Calculate average and max difference
        $avgTiles = count($routes) > 0 ? $totalTilesAll / count($routes) : 0;
        $maxTiles = count($routeStats) > 0 ? max(array_column($routeStats, 'total_tiles')) : 0;
        $minTiles = count($routeStats) > 0 ? min(array_column($routeStats, 'total_tiles')) : 0;
        $maxDiff = $maxTiles - $minTiles;

        // palette for up to 20 routes
        $routeColors = [
            '#FF0000','#00FF00','#0000FF','#FFA500','#800080',
            '#008080','#FFFF00','#FF00FF','#00FFFF','#A52A2A',
            '#4682B4','#32CD32','#FF6347','#8A2BE2','#2E8B57',
            '#DAA520','#D2691E','#9932CC','#FF4500','#696969'
        ];
        
        $data = compact(
            'routes',
            'routeColors',
            'routeStats',
            'totalTilesAll',
            'avgTiles',
            'maxDiff'
        );
        
        // Store in cache
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        // Add query debugging info
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);
        $data['queryCount'] = $queryCount;
        $data['queryLog'] = $queryLog;
        $data['fromCache'] = false;
        
        return view('routes.index', $data);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'num_routes' => 'required|integer|min:1'
        ]);
        $numRoutes = $request->input('num_routes');
    
        // Hardcoded starting location
        $startLocation = (object) [
            'id'        => 0,
            'name'      => 'Broekstraat 68',
            'latitude'  => 51.8372,
            'longitude' => 5.6697,
            'address'   => 'Broekstraat 68, Nederasselt',
        ];
    
        // Fetch *only* real locations from DB
        $locations = Location::all();
    
        if ($locations->isEmpty()) {
            return redirect()->back()->with('error', 'No locations available to generate routes.');
        }
    
        try {
            DB::beginTransaction();
    
            // Wipe old
            DB::table('route_location')->delete();
            Route::truncate();
    
            // Build a separate collection for distance matrix
            $allPoints = $locations->concat([$startLocation]);
            $distances = $this->calculateDistanceMatrix($allPoints);
    
            // Prepare unassigned REAL locations
            $unassigned = $locations->pluck('id')->toArray();
            $locationsPerRoute = ceil(count($unassigned) / $numRoutes);
            $createdRoutes = [];
            
            // Calculate total number of tiles to aim for equal distribution
            $totalTiles = $locations->sum('tegels_count');
            $idealTilesPerRoute = $totalTiles / $numRoutes;
    
            // First, create all the routes
            for ($i = 0; $i < $numRoutes; $i++) {
                $createdRoutes[] = Route::create(['name' => 'Route ' . ($i + 1)]);
            }
            
            // Sort locations by tile count (descending) to distribute high-tile locations first
            $locationsByTiles = $locations->sortByDesc('tegels_count')->values();
            
            // Assign locations with highest tile counts first to spread them evenly
            foreach ($locationsByTiles as $location) {
                if (empty($unassigned)) break;
                
                // Find route with lowest tile count so far
                $routeTileCounts = collect($createdRoutes)->map(function($route) {
                    return [
                        'route' => $route,
                        'tiles' => $route->locations()->sum('tegels_count'),
                        'count' => $route->locations()->count()
                    ];
                });
                
                $targetRoute = $routeTileCounts->sortBy('tiles')->first()['route'];
                $locId = $location->id;
                
                // If this route already has enough locations, find next best route
                if ($routeTileCounts->sortBy('tiles')->first()['count'] >= $locationsPerRoute) {
                    $targetRoute = $routeTileCounts->sortBy(function($item) {
                        return $item['count'] >= $locationsPerRoute ? PHP_INT_MAX : $item['tiles'];
                    })->first()['route'];
                }
                
                // For the first location in each route, use nearest-neighbor from start
                if ($targetRoute->locations()->count() === 0) {
                    $targetRoute->locations()->attach($locId, ['order' => 1]);
                    unset($unassigned[array_search($locId, $unassigned)]);
                    continue;
                }
                
                // For subsequent locations, try to optimize both by tile count and distance
                $lastLocId = $targetRoute->locations()->orderByDesc('route_location.order')->first()->id;
                
                $targetRoute->locations()->attach($locId, [
                    'order' => $targetRoute->locations()->count() + 1
                ]);
                unset($unassigned[array_search($locId, $unassigned)]);
            }
            
            // Optimize each route for distance using 2-opt
            foreach ($createdRoutes as $route) {
                $this->optimizeRoute($route);
            }
    
            DB::commit();
            return redirect()->route('routes.index')
                             ->with('success', 'Routes generated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                             ->with('error', 'Error generating routes: ' . $e->getMessage());
        }
    }
    

    public function update(Request $request, Route $route)
    {
        // Handle route name update from the form
        if ($request->has('name')) {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);
            
            $route->update(['name' => $request->name]);
            Cache::forget(self::CACHE_KEY . '_index');
            return redirect()->back()->with('success', 'Route naam bijgewerkt.');
        }
        
        // Handle location order update via JSON
        if ($request->has('locations')) {
            $request->validate([
                'locations' => 'required|array',
                'locations.*' => 'exists:locations,id'
            ]);
            
            foreach ($request->locations as $i => $lId) {
                $route->locations()->updateExistingPivot($lId, ['order' => $i + 1]);
            }
            
            Cache::forget(self::CACHE_KEY . '_index');
            return response()->json(['message' => 'Order updated']);
        }
        
        return redirect()->back()->with('error', 'Geen geldige gegevens ontvangen.');
    }

    public function moveLocation(Request $request)
    {
        $request->validate([
            'location_id'=>'required|exists:locations,id',
            'target_route_id'=>'required|exists:routes,id',
        ]);
        try {
            DB::beginTransaction();
            $loc  = Location::findOrFail($request->location_id);
            $to   = Route::findOrFail($request->target_route_id);
            $from = Route::whereHas('locations',fn($q)=>$q->where('location_id',$loc->id))->firstOrFail();
            
            // Check how this will affect tile distribution
            $fromTiles = $from->locations()->sum('tegels_count');
            $toTiles = $to->locations()->sum('tegels_count');
            $locTiles = $loc->tegels_count;
            
            // Calculate if the move will improve or worsen tile distribution
            $currentDiff = abs($fromTiles - $toTiles);
            $newDiff = abs(($fromTiles - $locTiles) - ($toTiles + $locTiles));
            
            // Perform the move
            $from->locations()->detach($loc->id);
            $to->locations()->attach($loc->id,['order'=>$to->locations()->count()+1]);
            DB::commit();
            
            // Clear cache
            Cache::forget(self::CACHE_KEY . '_index');
            
            $message = ['success'=>true];
            if ($newDiff > $currentDiff && $locTiles > 0) {
                $message['warning'] = 'Dit verslechtert de verdeling van tegels.';
            }
            
            return response()->json($message);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()],422);
        }
    }

    public function recalculateRoute(Request $request)
    {
        $request->validate(['route_id'=>'required|exists:routes,id']);
        try {
            DB::beginTransaction();
            $route = Route::findOrFail($request->route_id);
            $this->optimizeRoute($route);
            DB::commit();
            Cache::forget(self::CACHE_KEY . '_index');
            return response()->json(['success'=>true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()],422);
        }
    }

    public function destroy(Route $route)
    {
        DB::beginTransaction();
        try {
            $route->delete();
            DB::commit();
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_KEY . '_index');
            return redirect()->route('routes.index')->with('success','Route deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error','Error deleting: '.$e->getMessage());
        }
    }

    public function deleteAll()
    {
        try {
            DB::beginTransaction();
            DB::table('route_location')->delete();
            Route::truncate();
            DB::commit();
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_KEY . '_index');
            return redirect()->route('routes.index')->with('success','Alle routes verwijderd.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error','Fout verwijderen: '.$e->getMessage());
        }
    }

    // ————————————————————————————————————
    //  Distance / Optimization helpers below
    // ————————————————————————————————————

    private function calculateDistanceMatrix($points)
    {
        $d = [];
        foreach ($points as $i => $a) {
            foreach ($points as $j => $b) {
                if ($i !== $j) {
                    $d[$a->id][$b->id] = $this->calculateDistance(
                        $a->latitude, $a->longitude,
                        $b->latitude, $b->longitude
                    );
                }
            }
        }
        return $d;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // km
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);

        $a = sin($Δφ/2)**2 + cos($φ1)*cos($φ2)*sin($Δλ/2)**2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    private function findNearestLocation($currentId, $unassigned, $distances)
    {
        $best = null;
        $min  = PHP_FLOAT_MAX;
        foreach ($unassigned as $locId) {
            if (isset($distances[$currentId][$locId]) && $distances[$currentId][$locId] < $min) {
                $min  = $distances[$currentId][$locId];
                $best = $locId;
            }
        }
        return $best;
    }

    private function twoOptImprovement(array $route, array $distances)
    {
        $improved = true;
        $best    = $this->calculateRouteDistance($route, $distances);

        while ($improved) {
            $improved = false;
            for ($i = 0; $i < count($route) - 2; $i++) {
                for ($j = $i + 2; $j < count($route); $j++) {
                    $delta = $this->calculateTwoOptDelta($route, $i, $j, $distances);
                    if ($delta < 0) {
                        $this->reverseRouteSegment($route, $i+1, $j);
                        $best     += $delta;
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    private function calculateRouteDistance($route, $distances)
    {
        $sum = 0;
        for ($i = 0; $i < count($route) - 1; $i++) {
            $sum += $distances[$route[$i]][$route[$i+1]] ?? 0;
        }
        return $sum;
    }

    private function calculateTwoOptDelta($route, $i, $j, $distances)
    {
        // remove edges (i→i+1) and (j-1→j), add (i→j-1) and (i+1→j)
        $a = $route[$i];
        $b = $route[$i+1];
        $c = $route[$j-1];
        $d = $route[$j];
        $old = ($distances[$a][$b] ?? 0) + ($distances[$c][$d] ?? 0);
        $new = ($distances[$a][$c] ?? 0) + ($distances[$b][$d] ?? 0);
        return $new - $old;
    }

    private function reverseRouteSegment(array &$route, int $start, int $end)
    {
        while ($start < $end) {
            [$route[$start], $route[$end]] = [$route[$end], $route[$start]];
            $start++;
            $end--;
        }
    }

    private function optimizeRoute(Route $route)
    {
        $locs = $route->locations()->orderBy('route_location.order')->get()->all();
        if (count($locs) < 2) {
            return;
        }

        // extract IDs
        $seq = array_map(fn($l) => $l->id, $locs);

        // build matrix for these points
        $points = $route->locations->map(fn($l) => (object)[
            'id'        => $l->id,
            'latitude'  => $l->latitude,
            'longitude' => $l->longitude,
        ])->prepend((object)[
            'id'        => 0,
            'latitude'  => 51.8372,
            'longitude' => 5.6697
        ]);

        $distances = $this->calculateDistanceMatrix($points);
        $newSeq    = $this->twoOptImprovement($seq, $distances);

        // update pivot
        foreach ($newSeq as $i => $locId) {
            $route->locations()
                  ->updateExistingPivot($locId, ['order' => $i + 1]);
        }
    }
}
