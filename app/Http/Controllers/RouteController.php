<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RouteController extends Controller
{
    private const CACHE_TTL = 900;
    private const CACHE_KEY = 'routes';

    /**
     * Display a listing of routes for the selected date or all routes.
     */
    public function index()
    {
        $selectedDate = session('selected_date');
        
        // Refresh the session date to ensure it persists across requests
        if ($selectedDate) {
            session(['selected_date' => $selectedDate]);
            \Log::info('RouteController@index - Refreshed selected_date: ' . $selectedDate);
        }
        
        // Use either date or scheduled_date based on which one exists
        $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
        
        $query = Route::orderBy('created_at', 'desc');
        
        // Apply date filter if we have a selected date
        if ($selectedDate) {
            $query->whereDate($dateColumn, $selectedDate);
        }
        
        $routes = $query->get();
        
        // Format date for display if we have one
        $formattedDate = $selectedDate ? date('d-m-Y', strtotime($selectedDate)) : null;
        
        return view('routes.index', compact('routes', 'selectedDate', 'formattedDate'));
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
    
        // Get selected date from session
        $selectedDate = session('selected_date');
    
        // Fetch locations efficiently with single query, filtered by date if available
        $locationsQuery = Location::select(['id', 'name', 'latitude', 'longitude', 'address', 'tegels', 'tegels_count', 'tegels_type', 'begin_time', 'end_time', 'completion_minutes', 'date']);
        
        // Filter by date if a date is selected
        if ($selectedDate) {
            $locationsQuery->where(function($query) use ($selectedDate) {
                $query->where('date', $selectedDate)
                      ->orWhereNull('date');
            });
        }
        
        $locations = $locationsQuery->get();
    
        if ($locations->isEmpty()) {
            return redirect()->back()->with('error', 'No locations available to generate routes.');
        }
    
        try {
            DB::beginTransaction();
    
            // Wipe old with fewer queries
            Route::query()->delete(); // This will also delete pivot table entries with cascade
    
            // Build a separate collection for distance matrix
            $allPoints = $locations->concat([$startLocation]);
            
            // Use memoization for distance calculations
            $distances = $this->calculateDistanceMatrix($allPoints);
    
            // Prepare unassigned REAL locations
            $unassigned = $locations->pluck('id')->toArray();
            $locationsPerRoute = ceil(count($unassigned) / $numRoutes);
            
            // Calculate total number of tiles for distribution
            $totalTiles = 0;
            foreach ($locations as $location) {
                $totalTiles += $location->tegels ?? $location->tegels_count ?? 0;
            }
            $idealTilesPerRoute = $totalTiles / $numRoutes;
    
            // Create routes in a batch
            $routeNames = [];
            for ($i = 0; $i < $numRoutes; $i++) {
                $routeNames[] = ['name' => 'Route ' . ($i + 1)];
            }
            $createdRouteIds = Route::insert($routeNames);
            $createdRoutes = Route::orderBy('id')->get();
            
            // Sort locations by tile count (descending)
            $locationsByTiles = $locations->sortByDesc(function($location) {
                return $location->tegels ?? $location->tegels_count ?? 0;
            })->values();
            
            // Prepare batch array for pivot table
            $pivotData = [];
            
            // First pass: assign locations with highest tile counts
            foreach ($locationsByTiles as $location) {
                if (empty($unassigned)) break;
                
                // Find route with lowest tile count - use cached counts
                $routeTileCounts = [];
                foreach ($createdRoutes as $route) {
                    $routeId = $route->id;
                    if (!isset($routeTileCounts[$routeId])) {
                        $routeTileCounts[$routeId] = [
                            'route' => $route,
                            'tiles' => 0,
                            'count' => 0
                        ];
                    }
                    
                    // Update counts based on pivot data
                    foreach ($pivotData as $pivot) {
                        if ($pivot['route_id'] == $routeId) {
                            $locId = $pivot['location_id'];
                            $loc = $locations->firstWhere('id', $locId);
                            if ($loc) {
                                $tilesToAdd = $loc->tegels ?? $loc->tegels_count ?? 0;
                                $routeTileCounts[$routeId]['tiles'] += $tilesToAdd;
                                $routeTileCounts[$routeId]['count']++;
                            }
                        }
                    }
                }
                
                // Find target route
                usort($routeTileCounts, function($a, $b) use ($locationsPerRoute) {
                    if ($a['count'] >= $locationsPerRoute && $b['count'] < $locationsPerRoute) {
                        return 1;
                    }
                    if ($b['count'] >= $locationsPerRoute && $a['count'] < $locationsPerRoute) {
                        return -1;
                    }
                    return $a['tiles'] - $b['tiles'];
                });
                
                $targetRoute = $routeTileCounts[0]['route'];
                $locId = $location->id;
                
                // Calculate next order value
                $nextOrder = 1;
                foreach ($pivotData as $pivot) {
                    if ($pivot['route_id'] == $targetRoute->id) {
                        $nextOrder++;
                    }
                }
                
                // Add to pivot data
                $pivotData[] = [
                    'route_id' => $targetRoute->id,
                    'location_id' => $locId,
                    'order' => $nextOrder
                ];
                
                unset($unassigned[array_search($locId, $unassigned)]);
            }
            
            // Insert all pivot data in one go
            DB::table('route_location')->insert($pivotData);
            
            // Optimize each route for distance using 2-opt
            foreach ($createdRoutes as $route) {
                $this->optimizeRoute($route);
            }
            
            // Apply the new cross-route optimization algorithm
            $this->optimizeRoutesGlobally($createdRoutes, $locations, $distances);
    
            DB::commit();
            
            // Clear the cache
            Cache::forget(self::CACHE_KEY . '_index');
            
            // Further optimize all routes automatically using the optimizeAllRoutes method
            $optimizeResult = $this->optimizeAllRoutes(false);
            
            return redirect()->route('routes.index')
                             ->with('success', 'Routes generated and globally optimized successfully.');
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

    /**
     * Recalculate the route
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function recalculateRoute(int $id)
    {
        try {
            $route = Route::findOrFail($id);
            
            // Check if route belongs to authenticated user
            if ($route->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to route'
                ], 403);
            }
            
            $locationIds = $route->locations()->pluck('id')->toArray();
            
            if (count($locationIds) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least two locations are required for recalculation'
                ], 400);
            }
            
            // Get all locations with their details
            $locations = Location::whereIn('id', $locationIds)->get()->keyBy('id')->toArray();
            
            // Calculate distance matrix
            $distanceMatrix = [];
            foreach ($locationIds as $fromId) {
                $distanceMatrix[$fromId] = [];
                foreach ($locationIds as $toId) {
                    if ($fromId === $toId) {
                        $distanceMatrix[$fromId][$toId] = 0;
                    } else {
                        $fromLocation = $locations[$fromId];
                        $toLocation = $locations[$toId];
                        $distanceMatrix[$fromId][$toId] = $this->calculateDistance(
                            $fromLocation['latitude'], 
                            $fromLocation['longitude'], 
                            $toLocation['latitude'], 
                            $toLocation['longitude']
                        );
                    }
                }
            }
            
            $bestRoute = null;
            $bestValue = PHP_FLOAT_MAX;
            
            if (count($locationIds) <= 8) {
                // For small routes, try all permutations
                $firstId = $locationIds[0]; // Keep first location fixed
                $permutableIds = array_slice($locationIds, 1);
                
                $permutations = $this->generatePermutations($permutableIds);
                
                foreach ($permutations as $permutation) {
                    $route = array_merge([$firstId], $permutation);
                    $value = $this->evaluateSolution([$route], $distanceMatrix, $locations);
                    
                    if ($value < $bestValue) {
                        $bestValue = $value;
                        $bestRoute = $route;
                    }
                }
            } else {
                // For larger routes, use initial greedy solution + 2-opt
                // Start with the first location
                $currentId = $locationIds[0];
                $remainingIds = array_diff($locationIds, [$currentId]);
                $route = [$currentId];
                
                // Build initial greedy solution
                while (!empty($remainingIds)) {
                    $bestNextId = null;
                    $bestDistance = PHP_FLOAT_MAX;
                    
                    foreach ($remainingIds as $nextId) {
                        $distance = $distanceMatrix[$currentId][$nextId];
                        if ($distance < $bestDistance) {
                            $bestDistance = $distance;
                            $bestNextId = $nextId;
                        }
                    }
                    
                    $route[] = $bestNextId;
                    $currentId = $bestNextId;
                    $remainingIds = array_diff($remainingIds, [$bestNextId]);
                }
                
                // Apply 2-opt improvement
                $bestRoute = $this->twoOptImprovement($route, $distanceMatrix);
                $bestValue = $this->evaluateSolution([$bestRoute], $distanceMatrix, $locations);
            }
            
            if ($bestRoute) {
                DB::beginTransaction();
                try {
                    // Clear existing order values
                    $route->locations()->update(['order' => null]);
                    
                    // Set new order for each location
                    foreach ($bestRoute as $index => $locationId) {
                        $route->locations()->updateExistingPivot($locationId, ['order' => $index]);
                    }
                    
                    DB::commit();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Route recalculated successfully',
                        'route' => $bestRoute,
                        'value' => $bestValue
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Could not find an optimal route'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recalculating route: ' . $e->getMessage()
            ], 500);
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
        // Use sparse matrix approach: only calculate distances that are needed
        // and store them when requested
        $d = [];
        $calculatedPairs = 0;
        $maxCalculations = 5000; // Limit total calculations for very large datasets
        
        // Pre-calculate for critical points first
        foreach ($points as $i => $a) {
            // Always connect to starting point (id 0)
            if ($a->id !== 0) {
                $startPoint = $this->findPointById($points, 0);
                $d[$a->id][0] = $d[0][$a->id] = $this->calculateDistance(
                    $a->latitude, $a->longitude,
                    $startPoint->latitude, $startPoint->longitude
                );
                $calculatedPairs++;
            }
            
            // If too many points, only calculate to nearest neighbors
            if (count($points) > 50 && $calculatedPairs > $maxCalculations) {
                break;
            }
            
            // Calculate remaining distances with potential early stopping
            foreach ($points as $j => $b) {
                if ($i !== $j && $a->id !== $b->id && !isset($d[$a->id][$b->id])) {
                    $d[$a->id][$b->id] = $this->calculateDistance(
                        $a->latitude, $a->longitude,
                        $b->latitude, $b->longitude
                    );
                    $d[$b->id][$a->id] = $d[$a->id][$b->id]; // Use symmetry
                    $calculatedPairs += 2;
                    
                    // If too many calculations, stop
                    if ($calculatedPairs > $maxCalculations) {
                        break 2;
                    }
                }
            }
        }
        
        return $d;
    }
    
    private function findPointById($points, $id)
    {
        foreach ($points as $point) {
            if ($point->id === $id) {
                return $point;
            }
        }
        return null;
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

    /**
     * Improve a route using 2-opt algorithm
     *
     * @param array $route Array of location IDs
     * @param array $distanceMatrix Distance matrix
     * @return array Improved route
     */
    private function twoOptImprovement($route, $distanceMatrix)
    {
        $n = count($route);
        $improved = true;
        $iterations = 0;
        $maxIterations = 1000; // Prevent infinite loops
        
        while ($improved && $iterations < $maxIterations) {
            $improved = false;
            $iterations++;
            
            for ($i = 0; $i < $n - 2; $i++) {
                for ($j = $i + 2; $j < $n; $j++) {
                    // Calculate current cost
                    $current = $distanceMatrix[$route[$i]][$route[$i+1]] + 
                               $distanceMatrix[$route[$j-1]][$route[$j]];
                    
                    // Calculate potential swap cost
                    $swapped = $distanceMatrix[$route[$i]][$route[$j-1]] + 
                               $distanceMatrix[$route[$i+1]][$route[$j]];
                    
                    // If swap is better, perform it
                    if ($swapped < $current) {
                        // Reverse the segment between i+1 and j-1
                        $segment = array_slice($route, $i + 1, $j - $i - 1);
                        $segment = array_reverse($segment);
                        
                        // Replace the segment in the route
                        array_splice($route, $i + 1, $j - $i - 1, $segment);
                        
                        $improved = true;
                    }
                }
            }
        }
        
        return $route;
    }

    /**
     * Generate all permutations of an array
     *
     * @param array $items Array of items
     * @return array Array of permutations
     */
    private function generatePermutations($items) 
    {
        if (count($items) <= 1) {
            return [$items];
        }
        
        $result = [];
        
        // Keep the first item fixed and permute the rest
        $firstItem = $items[0];
        $remaining = array_slice($items, 1);
        
        // Get permutations of remaining items
        $permutationsOfRemaining = $this->generatePermutations($remaining);
        
        // Insert first item in every possible position
        foreach ($permutationsOfRemaining as $permutation) {
            // Keep first item at the beginning (start point)
            $result[] = array_merge([$firstItem], $permutation);
        }
        
        return $result;
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

    /**
     * Optimize routes globally by moving locations between routes
     * 
     * @param Collection $routes The routes to optimize
     * @param Collection $locations All locations across all routes
     * @param array $distanceMatrix Distance matrix between locations
     * @return array Result of optimization including optimized routes and stats
     */
    private function optimizeRoutesGlobally($routes, $locations, $distanceMatrix)
    {
        // Create a map of locations by ID for quick lookup
        $locationsById = [];
        foreach ($locations as $location) {
            $locationsById[$location->id] = $location;
        }
        
        // Working copy of routes
        $workingRoutes = [];
        foreach ($routes as $route) {
            $routeLocationsIds = $route->locations->pluck('id')->toArray();
            
            // Calculate total tile count and minutes for this route
            $totalTiles = 0;
            $totalMinutes = 0;
            foreach ($route->locations as $location) {
                $totalTiles += $location->tegels ?? 0;
                $totalMinutes += $location->completion_minutes ?? 0;
            }
            
            $workingRoutes[$route->id] = [
                'id' => $route->id,
                'order' => $routeLocationsIds,
                'capacity' => $route->capacity ?? PHP_INT_MAX, // If no capacity defined, use max int
                'current_tiles' => $totalTiles,
                'current_completion_minutes' => $totalMinutes,
                'start_time' => $route->start_time ?? '08:00:00',
                'max_duration_minutes' => $route->max_duration_minutes ?? 480, // Default to 8 hours if not set
            ];
        }
        
        // Initial temperature for simulated annealing
        $temperature = 100.0;
        $coolingRate = 0.99;
        $iterationLimit = 1000;
        $noImprovementLimit = 100;
        
        // Calculate initial total distance
        $initialTotalDistance = $this->calculateTotalDistance($workingRoutes, $locationsById, $distanceMatrix);
        $bestDistance = $initialTotalDistance;
        $bestRoutes = $workingRoutes;
        
        // Stats
        $locationsMovedCount = 0;
        $noImprovementCounter = 0;
        
        // Main optimization loop
        for ($iteration = 0; $iteration < $iterationLimit; $iteration++) {
            // Get two different random routes
            $routeIds = array_keys($workingRoutes);
            if (count($routeIds) < 2) break;
            
            $srcRouteKey = $routeIds[array_rand($routeIds)];
            do {
                $dstRouteKey = $routeIds[array_rand($routeIds)];
            } while ($srcRouteKey == $dstRouteKey);
            
            $srcRoute = &$workingRoutes[$srcRouteKey];
            $dstRoute = &$workingRoutes[$dstRouteKey];
            
            // Skip if source route is empty
            if (count($srcRoute['order']) == 0) continue;
            
            // Pick a random location from source route
            $srcLocationIdx = array_rand($srcRoute['order']);
            $locationId = $srcRoute['order'][$srcLocationIdx];
            $location = $locationsById[$locationId];
            
            // Check capacity constraint for destination route
            $locationTiles = $location->tegels ?? 0;
            $locationMinutes = $location->completion_minutes ?? 0;
            
            if (isset($dstRoute['capacity']) && 
                ($dstRoute['current_tiles'] + $locationTiles > $dstRoute['capacity'])) {
                continue; // Skip this move if it violates capacity
            }
            
            // Create temporary copy of routes for trial move
            $tempSrcRoute = $srcRoute;
            $tempDstRoute = $dstRoute;
            
            // Remove location from source route
            unset($tempSrcRoute['order'][$srcLocationIdx]);
            $tempSrcRoute['order'] = array_values($tempSrcRoute['order']); // Reindex
            $tempSrcRoute['current_tiles'] -= $locationTiles;
            $tempSrcRoute['current_completion_minutes'] -= $locationMinutes;
            
            // Find best position to insert in destination route
            $bestPosition = 0;
            $bestInsertionCost = PHP_FLOAT_MAX;
            
            // Try each possible insertion position
            for ($i = 0; $i <= count($tempDstRoute['order']); $i++) {
                $tempInsertRoute = $tempDstRoute;
                
                // Insert location at position i
                array_splice($tempInsertRoute['order'], $i, 0, [$locationId]);
                $tempInsertRoute['current_tiles'] += $locationTiles;
                $tempInsertRoute['current_completion_minutes'] += $locationMinutes;
                
                // Check for time window violations
                if ($this->checkTimeWindowViolations($tempInsertRoute, $locationsById)) {
                    continue; // Skip this position if it creates time window violations
                }
                
                // Calculate new distance for this insertion
                $newDstDistance = $this->calculateRouteTotalDistance(
                    $tempInsertRoute['order'], 
                    $locationsById, 
                    $distanceMatrix
                );
                
                if ($newDstDistance < $bestInsertionCost) {
                    $bestInsertionCost = $newDstDistance;
                    $bestPosition = $i;
                }
            }
            
            // Insert at best position in destination route
            array_splice($tempDstRoute['order'], $bestPosition, 0, [$locationId]);
            $tempDstRoute['current_tiles'] += $locationTiles;
            $tempDstRoute['current_completion_minutes'] += $locationMinutes;
            
            // Check time window constraints for updated routes
            $srcTimeViolation = $this->checkTimeWindowViolations($tempSrcRoute, $locationsById);
            $dstTimeViolation = $this->checkTimeWindowViolations($tempDstRoute, $locationsById);
            
            if ($srcTimeViolation || $dstTimeViolation) {
                continue; // Skip this move if it violates time windows
            }
            
            // Calculate new total distance
            $tempRoutes = $workingRoutes;
            $tempRoutes[$srcRouteKey] = $tempSrcRoute;
            $tempRoutes[$dstRouteKey] = $tempDstRoute;
            
            $newTotalDistance = $this->calculateTotalDistance($tempRoutes, $locationsById, $distanceMatrix);
            
            // Decide whether to accept the move
            $deltaDistance = $newTotalDistance - $bestDistance;
            
            $acceptProbability = ($deltaDistance < 0) ? 1.0 : exp(-$deltaDistance / $temperature);
            
            if ($acceptProbability > mt_rand() / mt_getrandmax()) {
                // Accept the move
                $workingRoutes[$srcRouteKey] = $tempSrcRoute;
                $workingRoutes[$dstRouteKey] = $tempDstRoute;
                $locationsMovedCount++;
                
                if ($newTotalDistance < $bestDistance) {
                    $bestDistance = $newTotalDistance;
                    $bestRoutes = $workingRoutes;
                    $noImprovementCounter = 0;
                } else {
                    $noImprovementCounter++;
                }
            } else {
                $noImprovementCounter++;
            }
            
            // Cool down temperature
            $temperature *= $coolingRate;
            
            // Break if no improvement for many iterations
            if ($noImprovementCounter >= $noImprovementLimit) break;
        }
        
        // Calculate improvement percentage
        $improvement = ($initialTotalDistance > 0) 
            ? (($initialTotalDistance - $bestDistance) / $initialTotalDistance) * 100.0 
            : 0.0;
        
        return [
            'routes' => $bestRoutes,
            'initial_distance' => $initialTotalDistance,
            'final_distance' => $bestDistance,
            'improvement' => $improvement,
            'locations_moved' => $locationsMovedCount,
            'iterations' => $iteration,
        ];
    }
    
    /**
     * Check if a route has time window violations
     * 
     * @param array $route Route data
     * @param array $locationsById Map of locations by ID
     * @return boolean True if violations exist, false otherwise
     */
    private function checkTimeWindowViolations($route, $locationsById)
    {
        // If route is empty, there are no violations
        if (empty($route['order'])) return false;
        
        $currentTime = strtotime($route['start_time']);
        $serviceTimeTotal = 0;
        $lastLocationId = null;
        
        foreach ($route['order'] as $i => $locationId) {
            $location = $locationsById[$locationId];
            
            // Add travel time from previous location if not first stop
            if ($lastLocationId !== null) {
                // Estimate travel time based on distance (assuming 50km/h average speed)
                $distance = $this->calculateDistanceBetweenLocations(
                    $locationsById[$lastLocationId], 
                    $location
                );
                $travelTimeMinutes = ($distance / 50) * 60; // Convert to minutes
                $currentTime += $travelTimeMinutes * 60; // Convert to seconds
            }
            
            // Check if we arrive after end time
            if ($location->endtime && $currentTime > strtotime($location->endtime)) {
                return true; // Time window violation
            }
            
            // Wait if we arrived before begin time
            if ($location->begintime && $currentTime < strtotime($location->begintime)) {
                $currentTime = strtotime($location->begintime);
            }
            
            // Add service time
            $serviceTimeMinutes = $location->completion_minutes ?? 0;
            $currentTime += $serviceTimeMinutes * 60; // Convert to seconds
            $serviceTimeTotal += $serviceTimeMinutes;
            
            $lastLocationId = $locationId;
        }
        
        // Check total route duration
        $routeDurationMinutes = ($currentTime - strtotime($route['start_time'])) / 60;
        if ($routeDurationMinutes > $route['max_duration_minutes']) {
            return true; // Max duration violation
        }
        
        return false;
    }
    
    /**
     * Calculate total distance for all routes
     * 
     * @param array $routes Working routes
     * @param array $locationsById Locations map by ID
     * @param array $distanceMatrix Distance matrix
     * @return float Total distance across all routes
     */
    private function calculateTotalDistance($routes, $locationsById, $distanceMatrix)
    {
        $totalDistance = 0;
        
        foreach ($routes as $route) {
            $totalDistance += $this->calculateRouteTotalDistance(
                $route['order'], 
                $locationsById, 
                $distanceMatrix
            );
        }
        
        return $totalDistance;
    }
    
    /**
     * Calculate distance for a single route
     * 
     * @param array $locationIds Array of location IDs in order
     * @param array $locationsById Locations map by ID
     * @param array $distanceMatrix Distance matrix
     * @return float Total route distance
     */
    private function calculateRouteTotalDistance($locationIds, $locationsById, $distanceMatrix)
    {
        if (count($locationIds) <= 1) return 0;
        
        $totalDistance = 0;
        $prevLocationId = null;
        
        foreach ($locationIds as $locationId) {
            if ($prevLocationId !== null) {
                // Use distance matrix if available
                if (isset($distanceMatrix[$prevLocationId][$locationId])) {
                    $totalDistance += $distanceMatrix[$prevLocationId][$locationId];
                } else {
                    // Fallback to direct calculation
                    $totalDistance += $this->calculateDistanceBetweenLocations(
                        $locationsById[$prevLocationId], 
                        $locationsById[$locationId]
                    );
                }
            }
            $prevLocationId = $locationId;
        }
        
        return $totalDistance;
    }

    /**
     * Calculate the total time required to travel from one point to another
     * This includes travel time and service time at the destination
     *
     * @param array $fromLocation
     * @param array $toLocation
     * @param float $distance
     * @return float Time in minutes
     */
    private function calculateTravelTime($fromLocation, $toLocation, $distance = null)
    {
        // Calculate distance if not provided
        if ($distance === null) {
            $distance = $this->calculateDistance(
                $fromLocation['latitude'],
                $fromLocation['longitude'],
                $toLocation['latitude'],
                $toLocation['longitude']
            );
        }
        
        // Assume average speed of 30 km/h in urban areas
        $averageSpeedKmh = 30; 
        
        // Convert to meters per minute
        $speedMpm = ($averageSpeedKmh * 1000) / 60;
        
        // Travel time in minutes
        $travelTime = $distance / $speedMpm;
        
        // Add service time at destination (tiles * completion time per tile)
        $serviceTileTime = isset($toLocation['tegels']) && isset($toLocation['completion_minutes']) 
            ? $toLocation['tegels'] * $toLocation['completion_minutes'] 
            : 0;
            
        // If we have a fixed completion time, use that instead
        $serviceTime = isset($toLocation['completion_minutes']) ? $toLocation['completion_minutes'] : 0;
        
        return $travelTime + max($serviceTileTime, $serviceTime);
    }

    /**
     * Check if a route solution is feasible considering time windows
     *
     * @param array $route Array of location IDs
     * @param array $distanceMatrix Distance matrix between locations
     * @param array $locations Array of location data
     * @return array [isFeasible, violations, waitTime, arrivalTimes]
     */
    private function checkTimeWindowFeasibility($route, $distanceMatrix, $locations)
    {
        $currentTime = 480; // Start at 8:00 AM (minutes since midnight)
        $violations = 0;
        $waitTime = 0;
        $arrivalTimes = [];
        
        for ($i = 0; $i < count($route); $i++) {
            $locationId = $route[$i];
            $location = $locations[$locationId];
            
            if ($i > 0) {
                $prevLocationId = $route[$i - 1];
                $prevLocation = $locations[$prevLocationId];
                $distance = $distanceMatrix[$prevLocationId][$locationId];
                
                // Add travel time
                $travelTime = $this->calculateTravelTime($prevLocation, $location, $distance);
                $currentTime += $travelTime;
            }
            
            // Store arrival time
            $arrivalTimes[$locationId] = $currentTime;
            
            // Check begintime constraint (earliest time)
            if (isset($location['begintime']) && $location['begintime']) {
                $beginMinutes = $this->timeToMinutes($location['begintime']);
                
                if ($currentTime < $beginMinutes) {
                    // Need to wait until location opens
                    $waitingTime = $beginMinutes - $currentTime;
                    $waitTime += $waitingTime;
                    $currentTime = $beginMinutes;
                }
            }
            
            // Check endtime constraint (latest time)
            if (isset($location['endtime']) && $location['endtime']) {
                $endMinutes = $this->timeToMinutes($location['endtime']);
                
                if ($currentTime > $endMinutes) {
                    // Arrived too late
                    $violations += ($currentTime - $endMinutes);
                }
            }
            
            // Add service time
            $serviceTime = isset($location['completion_minutes']) ? $location['completion_minutes'] : 0;
            $currentTime += $serviceTime;
        }
        
        return [
            'isFeasible' => ($violations === 0),
            'violations' => $violations,
            'waitTime' => $waitTime,
            'arrivalTimes' => $arrivalTimes
        ];
    }
    
    /**
     * Convert time string to minutes since midnight
     *
     * @param string $timeStr Time in format HH:MM:SS or HH:MM
     * @return int Minutes since midnight
     */
    private function timeToMinutes($timeStr)
    {
        if (!$timeStr) return 0;
        
        $parts = explode(':', $timeStr);
        $hours = intval($parts[0]);
        $minutes = intval($parts[1]);
        
        return ($hours * 60) + $minutes;
    }

    /**
     * Evaluate a solution based on multiple objectives:
     * - Total distance across all routes
     * - Time window violations
     * - Load balancing between routes (distance and workload)
     * - Wait time
     *
     * @param array $solution
     * @param array $distanceMatrix
     * @param array $locations
     * @return float
     */
    private function evaluateSolution($solution, $distanceMatrix, $locations)
    {
        $totalDistance = 0;
        $timeWindowViolations = 0;
        $routeLengths = [];
        $routeWorkloads = [];
        $totalWaitTime = 0;
        
        foreach ($solution as $routeId => $locationOrder) {
            $routeDistance = 0;
            $routeWorkload = 0;
            
            // Calculate route distance and check time windows
            if (count($locationOrder) > 0) {
                // Calculate route distance
                for ($i = 0; $i < count($locationOrder) - 1; $i++) {
                    $fromId = $locationOrder[$i];
                    $toId = $locationOrder[$i + 1];
                    
                    if (isset($distanceMatrix[$fromId][$toId])) {
                        $routeDistance += $distanceMatrix[$fromId][$toId];
                    }
                }
                
                // Check time window constraints
                $feasibility = $this->checkTimeWindowFeasibility($locationOrder, $distanceMatrix, $locations);
                $timeWindowViolations += $feasibility['violations'];
                $totalWaitTime += $feasibility['waitTime'];
                
                // Calculate workload (service time)
                foreach ($locationOrder as $locationId) {
                    if (isset($locations[$locationId])) {
                        $location = $locations[$locationId];
                        $tegels = isset($location['tegels']) ? $location['tegels'] : 0;
                        $completionMinutes = isset($location['completion_minutes']) ? $location['completion_minutes'] : 0;
                        
                        // If completion_minutes is set, use it; otherwise calculate based on tegels
                        $serviceTime = $completionMinutes > 0 ? $completionMinutes : ($tegels * 2); // 2 min per tile
                        $routeWorkload += $serviceTime;
                    }
                }
            }
            
            $totalDistance += $routeDistance;
            $routeLengths[$routeId] = $routeDistance;
            $routeWorkloads[$routeId] = $routeWorkload;
        }
        
        // Calculate distance load balancing penalty (standard deviation of route lengths)
        $avgRouteLength = count($routeLengths) > 0 ? array_sum($routeLengths) / count($routeLengths) : 0;
        $distanceVarianceSum = 0;
        
        foreach ($routeLengths as $length) {
            $distanceVarianceSum += pow($length - $avgRouteLength, 2);
        }
        
        $distanceLoadBalancingPenalty = count($routeLengths) > 0 
            ? sqrt($distanceVarianceSum / count($routeLengths)) 
            : 0;
        
        // Calculate workload balancing penalty (standard deviation of workloads)
        $avgWorkload = count($routeWorkloads) > 0 ? array_sum($routeWorkloads) / count($routeWorkloads) : 0;
        $workloadVarianceSum = 0;
        
        foreach ($routeWorkloads as $workload) {
            $workloadVarianceSum += pow($workload - $avgWorkload, 2);
        }
        
        $workloadBalancingPenalty = count($routeWorkloads) > 0 
            ? sqrt($workloadVarianceSum / count($routeWorkloads)) 
            : 0;
        
        // Weighted sum of objectives
        $distanceWeight = 1.0;
        $timeWindowWeight = 20.0; // High penalty for time window violations
        $distanceBalancingWeight = 0.3;
        $workloadBalancingWeight = 0.7;
        $waitTimeWeight = 0.1; // Small penalty for wait time
        
        return ($distanceWeight * $totalDistance) + 
               ($timeWindowWeight * $timeWindowViolations) + 
               ($distanceBalancingWeight * $distanceLoadBalancingPenalty) +
               ($workloadBalancingWeight * $workloadBalancingPenalty) +
               ($waitTimeWeight * $totalWaitTime);
    }

    /**
     * Optimize all routes using simulated annealing to find global optimum across routes
     *
     * @param Collection $routes Collection of Route models
     * @param array $distanceMatrix Distance matrix between locations
     * @param Collection $locations Collection of Location models
     * @return array The optimized solution
     */
    private function optimizeAllRoutesWithSA($routes, $distanceMatrix, $locations)
    {
        // Convert routes to a solution format: [routeId => [locationIds]]
        $currentSolution = [];
        foreach ($routes as $route) {
            $currentSolution[$route->id] = $route->locations->pluck('id')->toArray();
        }
        
        // Initial parameters for simulated annealing
        $initialTemperature = 100.0;
        $finalTemperature = 0.1;
        $coolingRate = 0.98;
        $currentTemperature = $initialTemperature;
        $maxIterations = 1000;
        $iterationsWithoutImprovement = 0;
        $maxIterationsWithoutImprovement = 100;
        $reheatingThreshold = 0.2; // When to reheat
        
        // Evaluate initial solution
        $currentEnergy = $this->evaluateSolution($currentSolution, $distanceMatrix, $locations);
        $bestSolution = $currentSolution;
        $bestEnergy = $currentEnergy;
        
        $iteration = 0;
        while ($currentTemperature > $finalTemperature && $iteration < $maxIterations) {
            // Generate neighbor solution
            $newSolution = $this->generateNeighborSolution($currentSolution);
            
            // Evaluate new solution
            $newEnergy = $this->evaluateSolution($newSolution, $distanceMatrix, $locations);
            
            // Decide whether to accept the new solution
            $acceptNewSolution = false;
            
            if ($newEnergy < $currentEnergy) {
                // Always accept better solutions
                $acceptNewSolution = true;
                $iterationsWithoutImprovement = 0;
                
                // Update best solution if this is the best so far
                if ($newEnergy < $bestEnergy) {
                    $bestSolution = $newSolution;
                    $bestEnergy = $newEnergy;
                }
            } else {
                // Accept worse solutions with a probability based on temperature
                $delta = $newEnergy - $currentEnergy;
                $probability = exp(-$delta / $currentTemperature);
                
                if (mt_rand() / mt_getrandmax() < $probability) {
                    $acceptNewSolution = true;
                }
                
                $iterationsWithoutImprovement++;
            }
            
            // Update current solution if accepted
            if ($acceptNewSolution) {
                $currentSolution = $newSolution;
                $currentEnergy = $newEnergy;
            }
            
            // Adaptive cooling: slow down cooling if we're making progress
            $adaptiveCoolingRate = ($iterationsWithoutImprovement < $maxIterationsWithoutImprovement / 2) 
                ? $coolingRate  // normal cooling
                : $coolingRate * 0.95; // faster cooling if stuck
            
            // Cool down temperature
            $currentTemperature *= $adaptiveCoolingRate;
            
            // Reheat if we've been stuck for too long but not at very low temperatures
            if ($iterationsWithoutImprovement >= $maxIterationsWithoutImprovement && 
                $currentTemperature > $finalTemperature * 10) {
                // Reheat to a fraction of the initial temperature
                $currentTemperature = $initialTemperature * $reheatingThreshold;
                $reheatingThreshold *= 0.9; // Reduce reheating threshold for next time
                $iterationsWithoutImprovement = 0;
            }
            
            $iteration++;
        }
        
        return $bestSolution;
    }

    /**
     * Generate a neighbor solution by either moving or swapping locations between routes
     *
     * @param array $currentSolution
     * @return array
     */
    private function generateNeighborSolution($currentSolution)
    {
        $newSolution = $currentSolution;
        $routeIds = array_keys($newSolution);
        
        if (count($routeIds) < 1) {
            return $currentSolution;
        }

        // Randomly choose operation: move or swap (70% move, 30% swap)
        $operation = (mt_rand(1, 100) <= 70) ? 'move' : 'swap';
        
        if ($operation === 'move') {
            // Select source route with at least 2 locations
            $validSourceRoutes = [];
            foreach ($routeIds as $routeId) {
                if (count($newSolution[$routeId]) > 1) {
                    $validSourceRoutes[] = $routeId;
                }
            }
            
            if (empty($validSourceRoutes)) {
                return $currentSolution;
            }
            
            $sourceRouteId = $validSourceRoutes[array_rand($validSourceRoutes)];
            $targetRouteId = $routeIds[array_rand($routeIds)];
            
            // Make sure we don't select the same route
            while ($targetRouteId == $sourceRouteId && count($routeIds) > 1) {
                $targetRouteId = $routeIds[array_rand($routeIds)];
            }
            
            // Select a random location from source route
            $locationIndexToMove = array_rand($newSolution[$sourceRouteId]);
            $locationToMove = $newSolution[$sourceRouteId][$locationIndexToMove];
            
            // Remove location from source route
            unset($newSolution[$sourceRouteId][$locationIndexToMove]);
            $newSolution[$sourceRouteId] = array_values($newSolution[$sourceRouteId]);
            
            // Add location to target route at a random position
            $targetPosition = rand(0, count($newSolution[$targetRouteId]));
            array_splice($newSolution[$targetRouteId], $targetPosition, 0, [$locationToMove]);
        } else {
            // Swap operation: exchange locations between two routes
            
            // Need at least 2 routes with at least 1 location each
            $validRoutes = [];
            foreach ($routeIds as $routeId) {
                if (count($newSolution[$routeId]) > 0) {
                    $validRoutes[] = $routeId;
                }
            }
            
            if (count($validRoutes) < 2) {
                return $currentSolution;
            }
            
            // Select two different routes
            $routeIndex1 = array_rand($validRoutes);
            $routeId1 = $validRoutes[$routeIndex1];
            
            // Remove selected route from valid routes to ensure different routes
            unset($validRoutes[$routeIndex1]);
            $validRoutes = array_values($validRoutes);
            
            $routeId2 = $validRoutes[array_rand($validRoutes)];
            
            // Select random locations from each route
            $locationIndex1 = array_rand($newSolution[$routeId1]);
            $locationIndex2 = array_rand($newSolution[$routeId2]);
            
            // Swap locations
            $location1 = $newSolution[$routeId1][$locationIndex1];
            $location2 = $newSolution[$routeId2][$locationIndex2];
            
            $newSolution[$routeId1][$locationIndex1] = $location2;
            $newSolution[$routeId2][$locationIndex2] = $location1;
        }
        
        return $newSolution;
    }

    /**
     * Optimize all routes by redistributing locations across routes
     * using time windows, multi-objective optimization and simulated annealing
     *
     * @param bool $returnJson Whether to return a JSON response (for API calls) or silently optimize (for internal calls)
     * @return \Illuminate\Http\JsonResponse|bool
     */
    public function optimizeAllRoutes($returnJson = true)
    {
        try {
            // Get all routes without filtering by user_id
            $routes = Route::with('locations')->get();
            
            // Check if we have at least two routes
            if ($routes->count() < 2) {
                if ($returnJson) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You need at least two routes to perform cross-route optimization.'
                    ], 400);
                }
                return false;
            }

            // Collect all locations from all routes
            $locationIds = [];
            foreach ($routes as $route) {
                $locationIds = array_merge($locationIds, $route->locations->pluck('id')->toArray());
            }
            
            if (empty($locationIds)) {
                if ($returnJson) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No locations found across routes.'
                    ], 400);
                }
                return false;
            }

            $locations = Location::whereIn('id', $locationIds)->get()->keyBy('id');

            // Build distance matrix for all locations
            $distanceMatrix = [];
            foreach ($locationIds as $fromId) {
                if (!isset($locations[$fromId])) continue;
                
                $from = $locations[$fromId];
                $distanceMatrix[$fromId] = [];
                
                foreach ($locationIds as $toId) {
                    if (!isset($locations[$toId])) continue;
                    
                    $to = $locations[$toId];
                    $distanceMatrix[$fromId][$toId] = $this->calculateDistance(
                        $from->latitude, $from->longitude, 
                        $to->latitude, $to->longitude
                    );
                }
            }
            
            // Perform cross-route optimization
            $bestSolution = $this->optimizeAllRoutesWithSA($routes, $distanceMatrix, $locations);
            
            // Re-optimize each route individually with 2-opt
            foreach ($bestSolution as $routeId => $locationOrder) {
                if (count($locationOrder) > 2) {
                    $bestSolution[$routeId] = $this->twoOptImprovement(
                        $locationOrder, 
                        $distanceMatrix
                    );
                }
            }

            // Save the optimized routes
            DB::beginTransaction();
            try {
                foreach ($bestSolution as $routeId => $locationOrder) {
                    $route = $routes->firstWhere('id', $routeId);
                    if ($route) {
                        $route->locations()->detach();
                        
                        // Attach locations in the optimized order
                        foreach ($locationOrder as $index => $locationId) {
                            $route->locations()->attach($locationId, ['order' => $index]);
                        }
                    }
                }
                DB::commit();
                
                // Clear cache to reflect the new routes
                Cache::forget(self::CACHE_KEY . '_index');
                
                if ($returnJson) {
                    return response()->json([
                        'success' => true,
                        'message' => 'All routes have been optimized successfully!'
                    ]);
                }
                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error saving optimized routes: " . $e->getMessage());
                if ($returnJson) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occurred while saving the optimized routes.'
                    ], 500);
                }
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error during cross-route optimization: " . $e->getMessage());
            if ($returnJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred during optimization: ' . $e->getMessage()
                ], 500);
            }
            return false;
        }
    }

    /**
     * Generate routes from a day's locations
     */
    public function generateFromDay($date)
    {
        // Use the correct date column
        $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
        
        // Get routes for the specified date using the correct column
        $routes = Route::with(['locations' => function($query) {
            $query->orderBy('route_location.order', 'asc');
        }])
        ->where($dateColumn, $date)
        ->get();
        
        if ($routes->isEmpty()) {
            return redirect()->route('day-planner.edit', $date)
                ->with('error', 'Geen routes gevonden voor deze datum. Maak eerst routes aan.');
        }
        
        // Convert to a format for optimization
        $routeIds = $routes->pluck('id')->toArray();
        
        // Get all locations from these routes
        $locationIds = DB::table('route_location')
            ->whereIn('route_id', $routeIds)
            ->pluck('location_id')
            ->toArray();
            
        if (empty($locationIds)) {
            return redirect()->route('day-planner.edit', $date)
                ->with('error', 'Geen locaties gevonden voor deze datum. Voeg eerst locaties toe aan de routes.');
        }
        
        // Get all locations
        $locations = Location::whereIn('id', $locationIds)->get();
        
        // Check if we actually found any valid locations
        if ($locations->isEmpty()) {
            return redirect()->route('day-planner.edit', $date)
                ->with('error', 'Geen geldige locaties gevonden. Controleer of alle locaties correcte coördinaten hebben.');
        }
        
        // Start location (Broekstraat 68)
        $startLocation = (object) [
            'id'        => 0,
            'name'      => 'Broekstraat 68',
            'latitude'  => 51.8372,
            'longitude' => 5.6697,
            'address'   => 'Broekstraat 68, Nederasselt',
        ];
        
        // Build a separate collection for distance matrix
        $allPoints = $locations->concat([$startLocation]);
        
        // Calculate distances between all points
        $distanceMatrix = $this->calculateDistanceMatrix($allPoints);
        
        try {
            DB::beginTransaction();
            
            // Clear existing locations from routes
            foreach ($routes as $route) {
                $route->locations()->detach();
            }
            
            // Optimize all routes with the existing number of routes
            $this->optimizeRoutesGlobally($routes, $locations, $distanceMatrix);
            
            // Verify that each route has at least one location assigned
            $emptyRoutes = $routes->filter(function($route) {
                return $route->locations()->count() == 0;
            });
            
            if ($emptyRoutes->isNotEmpty()) {
                DB::rollBack();
                return redirect()->route('day-planner.edit', $date)
                    ->with('error', 'Kon niet alle routes optimaliseren. Er zijn niet genoeg locaties voor het aantal routes.');
            }
            
            DB::commit();
            
            return redirect()->route('day-planner.show', $date)
                ->with('success', 'Routes zijn geoptimaliseerd voor ' . Carbon::parse($date, 'Europe/Amsterdam')->format('d-m-Y') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('day-planner.show', $date)
                ->with('error', 'Er is een fout opgetreden: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            // Other validation rules...
        ]);
        
        try {
            DB::beginTransaction();
            
            $route = new Route();
            $route->name = $validated['name'];
            $route->date = Carbon::parse($validated['date'], 'Europe/Amsterdam')->format('Y-m-d');
            // Set other fields as needed
            $route->save();
            
            DB::commit();
            
            return redirect()->route('routes.show', $route->id)
                ->with('success', 'Route created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Error creating route: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function create()
    {
        return view('routes.create');
    }
}

