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
        // Cache routes and locations for 5 minutes
        $routes = Cache::remember('routes', 300, function () {
            return Route::with(['locations' => function ($query) {
                $query->select('id', 'name', 'address', 'latitude', 'longitude', 'person_capacity')
                    ->orderBy('order');
            }])->get();
        });

        $locations = Cache::remember('locations', 300, function () {
            return Location::select('id', 'name', 'address', 'latitude', 'longitude', 'person_capacity')
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
        $request->validate([
            'num_routes' => 'required|integer|min:1',
            'route_capacity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            // Get all locations except Overrijssel with optimized query
            $locations = Cache::remember('locations.except.overrijssel', 300, function () {
                return Location::where('name', '!=', 'Overrijssel')
                    ->select('id', 'name', 'person_capacity')
                    ->get();
            });
            
            // Check if Overrijssel exists, if not create it
            $overrijssel = Cache::remember('location.overrijssel', 300, function () {
                return Location::firstOrCreate(
                    ['name' => 'Overrijssel'],
                    [
                        'address' => 'Overrijssel, Netherlands',
                        'latitude' => 52.4383,
                        'longitude' => 6.4405,
                        'person_capacity' => 2
                    ]
                );
            });
            
            // Calculate total persons needed for all locations
            $totalPersons = $locations->sum('person_capacity');
            $personsPerRoute = $request->route_capacity;
            $requestedRoutes = $request->num_routes;
            
            // Calculate minimum number of routes needed based on total persons
            $minRoutesNeeded = ceil($totalPersons / $personsPerRoute);
            $numberOfRoutes = max($requestedRoutes, $minRoutesNeeded);
            
            // Delete existing routes
            Route::truncate();
            
            // Create new routes in batches
            $routes = [];
            $routeLocations = [];
            $currentLocationIndex = 0;
            $currentPersonCount = 0;
            
            for ($i = 0; $i < $numberOfRoutes; $i++) {
                $routes[] = [
                    'name' => 'Route ' . ($i + 1),
                    'description' => 'Automatically generated route ' . ($i + 1),
                    'person_capacity' => $personsPerRoute,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            // Insert routes in bulk
            Route::insert($routes);
            
            // Get the created routes
            $createdRoutes = Route::all();
            
            // Prepare route locations data
            foreach ($createdRoutes as $route) {
                $routeLocations[] = [
                    'route_id' => $route->id,
                    'location_id' => $overrijssel->id,
                    'order' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                foreach ($locations as $index => $location) {
                    $routeLocations[] = [
                        'route_id' => $route->id,
                        'location_id' => $location->id,
                        'order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            // Insert route locations in bulk
            DB::table('location_route')->insert($routeLocations);
            
            DB::commit();
            
            // Clear all relevant caches
            Cache::forget('routes');
            Cache::forget('locations');
            Cache::forget('locations.except.overrijssel');
            
            return redirect()->route('routes.index')
                ->with('success', 'Routes generated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error generating routes: ' . $e->getMessage());
        }
    }
} 