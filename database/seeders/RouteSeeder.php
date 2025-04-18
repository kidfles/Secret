<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Route;
use App\Models\Location;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing routes and pivot table
        DB::table('route_location')->delete();
        Route::truncate();

        // Find home location (Nederasselt)
        $baseLocation = Location::where('name', 'Nederasselt Base')->first();
        
        if (!$baseLocation) {
            $this->command->error('Error: Base location (Nederasselt) not found');
            return;
        }

        // Get locations with capacity >= 3
        $highCapacityLocations = Location::where('person_capacity', '>=', 3)->get();
        
        // Create standard route for Amsterdam
        $route1 = Route::create([
            'name' => 'Amsterdam Route',
            'description' => 'Route through Amsterdam Central'
        ]);
        
        // Get Amsterdam location
        $amsterdamLocation = Location::where('name', 'Amsterdam Centraal')->first();
        $rotterdamLocation = Location::where('name', 'Rotterdam Markthal')->first();
        
        // Add locations to route
        $this->addLocationsToRoute($route1, [$baseLocation, $amsterdamLocation, $rotterdamLocation, $baseLocation]);
        
        // Create routes for the high capacity locations
        $routeCounter = 1;
        foreach ($highCapacityLocations as $location) {
            // Create two routes for each high capacity location
            for ($i = 0; $i < 2; $i++) {
                $routeCounter++;
                $routeName = $location->city . ' Route ' . ($i + 1);
                
                $route = Route::create([
                    'name' => $routeName,
                    'description' => 'Route ' . ($i + 1) . ' through ' . $location->name
                ]);
                
                // Find another random location to add to the route
                $randomLocation = Location::where('id', '!=', $location->id)
                    ->where('id', '!=', $baseLocation->id)
                    ->inRandomOrder()
                    ->first();
                
                if ($i == 0) {
                    // First route: Base -> High Capacity Location -> Base
                    $this->addLocationsToRoute($route, [$baseLocation, $location, $baseLocation]);
                } else {
                    // Second route: Base -> High Capacity Location -> Random Location -> Base
                    $this->addLocationsToRoute($route, [$baseLocation, $location, $randomLocation, $baseLocation]);
                }
            }
        }
    }
    
    /**
     * Add locations to a route with proper ordering
     */
    private function addLocationsToRoute($route, $locations)
    {
        $order = 1;
        foreach ($locations as $location) {
            DB::table('route_location')->insert([
                'route_id' => $route->id,
                'location_id' => $location->id,
                'order' => $order,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $order++;
        }
    }
}
