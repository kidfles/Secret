<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        // Cache routes and locations for 15 minutes (increased from 5)
        $routes = Cache::remember('routes', 900, function () {
            return Route::with(['locations' => function ($query) {
                $query->select('locations.id', 'locations.name', 'locations.address', 'locations.latitude', 'locations.longitude', 'locations.person_capacity')
                    ->orderBy('route_location.order');
            }])->get();
        });

        // Only load locations if they're not already in the routes
        $locations = Cache::remember('available_locations', 900, function () use ($routes) {
            // Get all location IDs that are already in routes
            $usedLocationIds = collect();
            foreach ($routes as $route) {
                $usedLocationIds = $usedLocationIds->merge($route->locations->pluck('id'));
            }
            $usedLocationIds = $usedLocationIds->unique();
            
            // Only get locations that aren't in any route
            return Location::whereNotIn('id', $usedLocationIds)
                ->select('id', 'name', 'address', 'latitude', 'longitude', 'person_capacity')
                ->get();
        });

        return view('routes.index', compact('routes', 'locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'locations' => 'required|array|min:2',
            'locations.*' => 'exists:locations,id',
            'person_capacity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            $route = Route::create([
                'name' => $request->name,
                'description' => $request->description,
                'person_capacity' => $request->person_capacity,
            ]);

            $locations = collect($request->locations)->mapWithKeys(function ($id, $index) {
                return [$id => ['order' => $index]];
            })->toArray();

            $route->locations()->attach($locations);

            DB::commit();
            
            // Clear cache after successful creation
            Cache::forget('routes');
            
            return redirect()->route('routes.index')
                ->with('success', 'Route created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating route: ' . $e->getMessage());
        }
    }

    public function show(Route $route)
    {
        $route = Cache::remember('route.' . $route->id, 300, function () use ($route) {
            return $route->load(['locations' => function ($query) {
                $query->select('id', 'name', 'address', 'latitude', 'longitude', 'person_capacity')
                    ->orderBy('order');
            }]);
        });

        return view('routes.show', compact('route'));
    }

    public function destroy(Route $route)
    {
        DB::beginTransaction();
        try {
            $route->delete();
            DB::commit();
            
            // Clear cache after successful deletion
            Cache::forget('routes');
            Cache::forget('route.' . $route->id);
            
            return redirect()->route('routes.index')
                ->with('success', 'Route deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error deleting route: ' . $e->getMessage());
        }
    }

    public function generateRoutes(Request $request)
    {
        try {
            $validated = $request->validate([
                'num_routes' => 'required|integer|min:1',
                'route_capacity' => 'required|integer|min:1'
            ]);

            DB::beginTransaction();

            // Get all locations except Overrijssel
            $locations = Location::where('name', '!=', 'Overrijssel')
                ->get(['id', 'name', 'latitude', 'longitude', 'person_capacity'])
                ->toArray();

            // Ensure Overrijssel exists
            $overrijssel = Location::firstOrCreate(
                ['name' => 'Overrijssel'],
                [
                    'street' => 'Overrijssel',
                    'house_number' => '1',
                    'city' => 'Overrijssel',
                    'postal_code' => '8000',
                    'address' => 'Overrijssel, Netherlands',
                    'latitude' => 52.4383,
                    'longitude' => 6.4405,
                    'person_capacity' => 2
                ]
            );

            // Calculate total persons needed
            $totalPersons = array_sum(array_column($locations, 'person_capacity'));
            
            // Calculate minimum number of routes needed
            $minRoutes = ceil($totalPersons / $validated['route_capacity']);
            $numRoutes = max($minRoutes, $validated['num_routes']);

            // Clear existing routes
            DB::table('route_location')->truncate();
            Route::truncate();

            // Create new routes
            $routes = [];
            for ($i = 1; $i <= $numRoutes; $i++) {
                $routes[] = Route::create([
                    'name' => "Route $i",
                    'capacity' => $validated['route_capacity']
                ]);
            }

            // Distribute locations across routes using nearest neighbor algorithm
            $currentRouteIndex = 0;
            $currentRouteCapacity = 0;
            $visited = [];
            $currentLocation = $overrijssel;

            while (count($visited) < count($locations)) {
                $nearest = null;
                $minDistance = PHP_FLOAT_MAX;

                foreach ($locations as $index => $location) {
                    if (in_array($index, $visited)) continue;

                    $distance = $this->calculateDistance(
                        $currentLocation['latitude'],
                        $currentLocation['longitude'],
                        $location['latitude'],
                        $location['longitude']
                    );

                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $nearest = $index;
                    }
                }

                if ($nearest === null) break;

                $location = $locations[$nearest];
                $locationCapacity = $location['person_capacity'];

                if ($currentRouteCapacity + $locationCapacity > $validated['route_capacity']) {
                    $currentRouteIndex++;
                    $currentRouteCapacity = 0;
                    $currentLocation = $overrijssel;

                    if ($currentRouteIndex >= count($routes)) {
                        break;
                    }
                }

                $routes[$currentRouteIndex]->locations()->attach($location['id'], [
                    'order' => count($visited) + 1
                ]);

                $currentRouteCapacity += $locationCapacity;
                $visited[] = $nearest;
                $currentLocation = (object)$location;
            }

            DB::commit();

            // Clear route cache
            Cache::forget('routes');

            return redirect()->route('routes.index')
                ->with('success', 'Routes generated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('routes.index')
                ->with('error', 'Error generating routes: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate the distance between two points using the Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in km
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta/2) * sin($latDelta/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta/2) * sin($lonDelta/2);
            
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c; // Distance in km
    }
} 