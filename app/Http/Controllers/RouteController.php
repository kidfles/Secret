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
        $routes = Cache::remember('routes', 900, function () {
            return Route::with(['locations' => function ($query) {
                $query->select('locations.id', 'locations.name', 'locations.address', 'locations.latitude', 'locations.longitude', 'locations.person_capacity')
                    ->orderBy('route_location.order');
            }])->get();
        });

        return view('routes.index', compact('routes'));
    }

    public function destroy(Route $route)
    {
        DB::beginTransaction();
        try {
            $route->delete();
            DB::commit();
            
            Cache::forget('routes');
            
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

            Cache::forget('routes');

            return redirect()->route('routes.index')
                ->with('success', 'Routes generated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('routes.index')
                ->with('error', 'Error generating routes: ' . $e->getMessage());
        }
    }
    
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