<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::with('locations')->get();
        $locations = Location::all();
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

        $route = Route::create([
            'name' => $request->name,
            'description' => $request->description,
            'person_capacity' => $request->person_capacity,
        ]);

        $locations = collect($request->locations)->mapWithKeys(function ($id, $index) {
            return [$id => ['order' => $index]];
        })->toArray();

        $route->locations()->attach($locations);

        return redirect()->route('routes.index')
            ->with('success', 'Route created successfully.');
    }

    public function show(Route $route)
    {
        $route->load('locations');
        return view('routes.show', compact('route'));
    }

    public function destroy(Route $route)
    {
        $route->delete();
        return redirect()->route('routes.index')
            ->with('success', 'Route deleted successfully.');
    }

    public function generateRoutes(Request $request)
    {
        $request->validate([
            'num_routes' => 'required|integer|min:1',
            'route_capacity' => 'required|integer|min:1'
        ]);

        // Get all locations except Overrijssel
        $locations = Location::where('name', '!=', 'Overrijssel')->get();
        
        // Check if Overrijssel exists, if not create it
        $overrijssel = Location::where('name', 'Overrijssel')->first();
        if (!$overrijssel) {
            $overrijssel = Location::create([
                'name' => 'Overrijssel',
                'address' => 'Overrijssel, Netherlands',
                'latitude' => 52.4383,
                'longitude' => 6.4405,
                'person_capacity' => 2
            ]);
        }
        
        // Calculate total persons needed for all locations
        $totalPersons = $locations->sum('person_capacity');
        $personsPerRoute = $request->route_capacity;
        $requestedRoutes = $request->num_routes;
        
        // Calculate minimum number of routes needed based on total persons
        $minRoutesNeeded = ceil($totalPersons / $personsPerRoute);
        $numberOfRoutes = max($requestedRoutes, $minRoutesNeeded);
        
        // Delete existing routes
        Route::truncate();
        
        // Create new routes
        $currentLocationIndex = 0;
        $currentPersonCount = 0;
        
        for ($i = 0; $i < $numberOfRoutes; $i++) {
            $route = Route::create([
                'name' => 'Route ' . ($i + 1),
                'description' => 'Automatically generated route ' . ($i + 1),
                'person_capacity' => $personsPerRoute
            ]);
            
            // Always add Overrijssel as the first location
            $route->locations()->attach($overrijssel->id, ['order' => 0]);
            
            // Add locations to this route until we reach the person capacity
            $order = 1;
            $routePersonCount = 0;
            
            while ($currentLocationIndex < $locations->count() && $routePersonCount < $personsPerRoute) {
                $location = $locations[$currentLocationIndex];
                
                // If adding this location would exceed the route capacity, try the next route
                if (($routePersonCount + $location->person_capacity) > $personsPerRoute) {
                    break;
                }
                
                $route->locations()->attach($location->id, ['order' => $order]);
                $routePersonCount += $location->person_capacity;
                $currentPersonCount += $location->person_capacity;
                $currentLocationIndex++;
                $order++;
            }
        }
        
        $message = "Generated {$numberOfRoutes} routes with capacity of {$personsPerRoute} persons each. ";
        if ($numberOfRoutes > $requestedRoutes) {
            $message .= "Note: More routes were needed than requested to accommodate all persons.";
        }
        
        return redirect()->route('routes.index')
            ->with('success', $message);
    }
} 