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
        $routes = Route::with(['locations' => function ($q) {
            $q->orderBy('route_location.order');
        }])->get();

        // palette for up to 10 routes
        $routeColors = [
            '#FF0000','#00FF00','#0000FF','#FFA500','#800080',
            '#008080','#FFFF00','#FF00FF','#00FFFF','#A52A2A'
        ];

        return view('routes.index', compact('routes','routeColors'));
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
    
            for ($i = 0; $i < $numRoutes && $unassigned; $i++) {
                $route = Route::create(['name' => 'Route ' . ($i + 1)]);
                $sequence = [$startLocation->id];
    
                // Nearest‐neighbor on the REAL unassigned list
                while (
                    count($sequence) < min($locationsPerRoute + 1, count($unassigned) + 1)
                    && !empty($unassigned)
                ) {
                    $currentId = end($sequence);
                    $nextId = $this->findNearestLocation($currentId, $unassigned, $distances);
                    if ($nextId === null) break;
    
                    $sequence[] = $nextId;
                    unset($unassigned[array_search($nextId, $unassigned)]);
                }
    
                // 2‐Opt improvement
                $sequence = $this->twoOptImprovement($sequence, $distances);
    
                // Attach (skip the startLocation->id=0)
                foreach ($sequence as $order => $locId) {
                    if ($locId === $startLocation->id) {
                        continue;
                    }
                    $route->locations()->attach($locId, ['order' => $order]);
                }
    
                $createdRoutes[] = $route;
            }
    
            // Distribute any leftovers
            foreach ($unassigned as $locId) {
                $least = collect($createdRoutes)
                    ->sortBy(fn($r) => $r->locations()->count())
                    ->first();
    
                $least->locations()->attach($locId, [
                    'order' => $least->locations()->count() + 1,
                ]);
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
        $request->validate([
            'locations'=>'required|array',
            'locations.*'=>'exists:locations,id'
        ]);
        foreach ($request->locations as $i=>$lId) {
            $route->locations()->updateExistingPivot($lId,['order'=>$i+1]);
        }
        return response()->json(['message'=>'Order updated']);
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
            $from->locations()->detach($loc->id);
            $to->locations()->attach($loc->id,['order'=>$to->locations()->count()+1]);
            DB::commit();
            return response()->json(['success'=>true]);
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
