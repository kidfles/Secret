<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RouteController extends Controller
{
    private const CACHE_TTL = 900; // 15 minutes
    private const CACHE_KEY = 'routes';

    public function index()
    {
        $routes = Route::with(['locations' => function ($query) {
            $query->orderBy('route_location.order');
        }])->get();

        return view('routes.index', compact('routes'));
    }

    public function destroy(Route $route)
    {
        DB::beginTransaction();
        try {
            $route->delete();
            DB::commit();
            
            Cache::forget(self::CACHE_KEY);
            
            return redirect()->route('routes.index')
                ->with('success', 'Route deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error deleting route: ' . $e->getMessage());
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'num_routes' => 'required|integer|min:1'
        ]);

        $numRoutes = $request->input('num_routes');
        
        // Hardcoded starting location (Broekstraat 68, Nederasselt)
        $startLocation = new \stdClass();
        $startLocation->id = 0; // Use a special ID for the starting location
        $startLocation->name = 'Broekstraat 68';
        $startLocation->latitude = 51.8372; // Approximate coordinates for Nederasselt
        $startLocation->longitude = 5.6697;
        $startLocation->address = 'Broekstraat 68, Nederasselt';

        // Get all locations
        $locations = Location::all();

        if ($locations->isEmpty()) {
            return redirect()->back()->with('error', 'No locations available to generate routes.');
        }

        try {
            DB::beginTransaction();
            
            // Delete existing routes and their relationships
            DB::table('route_location')->delete();
            Route::truncate();

            // Calculate distances between all locations
            $distances = $this->calculateDistanceMatrix($locations->push($startLocation));

            // Calculate locations per route (excluding the start location)
            $locationsPerRoute = ceil($locations->count() / $numRoutes);
            $unassignedLocations = $locations->pluck('id')->toArray();
            $routes = [];

            // Generate optimized routes
            for ($i = 0; $i < $numRoutes && !empty($unassignedLocations); $i++) {
                // Create route in database
                $route = Route::create([
                    'name' => 'Route ' . ($i + 1)
                ]);

                // Always start with Broekstraat 68
                $routeLocations = [$startLocation->id];

                // Build route using nearest neighbor with 2-opt improvement
                $targetSize = min($locationsPerRoute + 1, count($unassignedLocations) + 1); // +1 for start location
                while (count($routeLocations) < $targetSize && !empty($unassignedLocations)) {
                    $currentLocationId = end($routeLocations);
                    $nearestLocationId = $this->findNearestLocation($currentLocationId, $unassignedLocations, $distances);
                    
                    if ($nearestLocationId === null) break;
                    
                    $routeLocations[] = $nearestLocationId;
                    unset($unassignedLocations[array_search($nearestLocationId, $unassignedLocations)]);
                }

                // Apply 2-opt improvement
                $routeLocations = $this->twoOptImprovement($routeLocations, $distances);

                // Attach locations in optimized order
                foreach ($routeLocations as $order => $locationId) {
                    // Skip the hardcoded starting location
                    if ($locationId === $startLocation->id) continue;
                    
                    $route->locations()->attach($locationId, ['order' => $order]);
                }

                $routes[] = $route;
            }

            // If there are still unassigned locations, distribute them to the routes
            if (!empty($unassignedLocations)) {
                foreach ($unassignedLocations as $locationId) {
                    // Find the route with the least locations
                    $targetRoute = collect($routes)->sortBy(function($route) {
                        return $route->locations()->count();
                    })->first();

                    // Add location to the route
                    $targetRoute->locations()->attach($locationId, [
                        'order' => $targetRoute->locations()->count() + 1
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('routes.index')->with('success', 'Routes generated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error generating routes: ' . $e->getMessage());
        }
    }

    private function calculateDistanceMatrix($locations)
    {
        $distances = [];
        foreach ($locations as $i => $loc1) {
            foreach ($locations as $j => $loc2) {
                if ($i !== $j) {
                    $distances[$loc1->id][$loc2->id] = $this->calculateDistance(
                        $loc1->latitude,
                        $loc1->longitude,
                        $loc2->latitude,
                        $loc2->longitude
                    );
                }
            }
        }
        return $distances;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    private function findNearestLocation($currentLocationId, $unassignedLocations, $distances)
    {
        $minDistance = PHP_FLOAT_MAX;
        $nearestLocationId = null;

        foreach ($unassignedLocations as $locationId) {
            if (isset($distances[$currentLocationId][$locationId])) {
                $distance = $distances[$currentLocationId][$locationId];
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestLocationId = $locationId;
                }
            }
        }

        return $nearestLocationId;
    }

    private function twoOptImprovement($route, $distances)
    {
        if (count($route) <= 2) {
            return $route; // No improvement possible for routes with 2 or fewer locations
        }

        $improved = true;
        $bestDistance = $this->calculateRouteDistance($route, $distances);

        while ($improved) {
            $improved = false;
            $bestDelta = 0;
            $bestI = -1;
            $bestJ = -1;

            // Try all possible 2-opt moves
            for ($i = 0; $i < count($route) - 2; $i++) {
                for ($j = $i + 2; $j < count($route); $j++) {
                    $delta = $this->calculateTwoOptDelta($route, $i, $j, $distances);
                    if ($delta < $bestDelta) {
                        $bestDelta = $delta;
                        $bestI = $i;
                        $bestJ = $j;
                    }
                }
            }

            // If we found an improvement, apply it
            if ($bestDelta < 0) {
                $this->reverseRouteSegment($route, $bestI + 1, $bestJ);
                $improved = true;
            }
        }

        return $route;
    }

    private function calculateRouteDistance($route, $distances)
    {
        $totalDistance = 0;
        for ($i = 0; $i < count($route) - 1; $i++) {
            if (isset($distances[$route[$i]][$route[$i + 1]])) {
                $totalDistance += $distances[$route[$i]][$route[$i + 1]];
            }
        }
        return $totalDistance;
    }

    private function calculateTwoOptDelta($route, $i, $j, $distances)
    {
        if (!isset($distances[$route[$i]][$route[$i + 1]]) || 
            !isset($distances[$route[$j - 1]][$route[$j]]) ||
            !isset($distances[$route[$i]][$route[$j - 1]]) ||
            !isset($distances[$route[$i + 1]][$route[$j]])) {
            return 0;
        }

        $oldDistance = $distances[$route[$i]][$route[$i + 1]] + $distances[$route[$j - 1]][$route[$j]];
        $newDistance = $distances[$route[$i]][$route[$j - 1]] + $distances[$route[$i + 1]][$route[$j]];
        return $newDistance - $oldDistance;
    }

    private function reverseRouteSegment(&$route, $start, $end)
    {
        if ($start >= $end || $start < 0 || $end >= count($route)) {
            return;
        }

        while ($start < $end) {
            $temp = $route[$start];
            $route[$start] = $route[$end];
            $route[$end] = $temp;
            $start++;
            $end--;
        }
    }

    public function update(Request $request, Route $route)
    {
        $request->validate([
            'locations' => 'required|array',
            'locations.*' => 'exists:locations,id'
        ]);

        // Update the order of locations in the route
        foreach ($request->locations as $index => $locationId) {
            $route->locations()->updateExistingPivot($locationId, ['order' => $index + 1]);
        }

        return response()->json(['message' => 'Route updated successfully']);
    }

    private function preCalculateDistances(Collection $locations, $overrijssel): array
    {
        $distances = [];
        foreach ($locations as $index => $location) {
            $distances[$index] = $this->calculateDistance(
                $overrijssel->latitude,
                $overrijssel->longitude,
                $location['latitude'],
                $location['longitude']
            );
        }
        return $distances;
    }

    /**
     * Move a location from one route to another.
     */
    public function moveLocation(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'source_route_id' => 'required|exists:routes,id',
            'target_route_id' => 'required|exists:routes,id',
        ]);

        try {
            DB::beginTransaction();

            $location = Location::findOrFail($request->location_id);
            $sourceRoute = Route::findOrFail($request->source_route_id);
            $targetRoute = Route::findOrFail($request->target_route_id);

            // Check if the location is in the source route
            if (!$sourceRoute->locations()->where('location_id', $location->id)->exists()) {
                throw new \Exception('De locatie bevindt zich niet in de bronroute.');
            }

            // Remove the location from the source route
            $sourceRoute->locations()->detach($location->id);

            // Add the location to the target route
            $targetRoute->locations()->attach($location->id, ['order' => $targetRoute->locations()->count() + 1]);

            // Recalculate the target route
            $this->optimizeRoute($targetRoute);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Locatie succesvol verplaatst.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Recalculate a route's order.
     */
    public function recalculateRoute(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:routes,id',
        ]);

        try {
            DB::beginTransaction();

            $route = Route::findOrFail($request->route_id);
            $this->optimizeRoute($route);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Route succesvol opnieuw berekend.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Helper method to optimize a route's order.
     */
    private function optimizeRoute(Route $route)
    {
        $locations = $route->locations()->get();
        
        if ($locations->isEmpty()) {
            return;
        }

        // Get the first location (starting point)
        $firstLocation = $locations->first();
        $remainingLocations = $locations->where('id', '!=', $firstLocation->id)->values();
        
        // Initialize the optimized route with the first location
        $optimizedRoute = collect([$firstLocation]);
        $currentLocation = $firstLocation;
        
        // Use nearest neighbor algorithm to optimize the route
        while ($remainingLocations->isNotEmpty()) {
            $nearestLocation = null;
            $minDistance = PHP_FLOAT_MAX;
            
            foreach ($remainingLocations as $location) {
                $distance = $this->calculateDistance(
                    $currentLocation->latitude,
                    $currentLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestLocation = $location;
                }
            }
            
            if ($nearestLocation) {
                $optimizedRoute->push($nearestLocation);
                $currentLocation = $nearestLocation;
                $remainingLocations = $remainingLocations->where('id', '!=', $nearestLocation->id)->values();
            }
        }
        
        // Update the order in the database
        foreach ($optimizedRoute as $index => $location) {
            $route->locations()->updateExistingPivot($location->id, ['order' => $index + 1]);
        }
    }

    /**
     * Delete all routes.
     */
    public function deleteAll()
    {
        try {
            DB::beginTransaction();
            
            // Delete all route relationships and routes
            DB::table('route_location')->delete();
            Route::truncate();
            
            DB::commit();
            return redirect()->route('routes.index')->with('success', 'Alle routes zijn verwijderd.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Fout bij het verwijderen van routes: ' . $e->getMessage());
        }
    }
} 