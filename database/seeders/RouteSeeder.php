<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Route;
use App\Models\Location;
use Carbon\Carbon;

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
            'description' => 'Route through Amsterdam Central',
            'start_time' => '08:00'
        ]);
        
        // Get Amsterdam location
        $amsterdamLocation = Location::where('name', 'Amsterdam Centraal')->first();
        $rotterdamLocation = Location::where('name', 'Rotterdam Markthal')->first();
        
        // Add locations to route
        $this->addLocationsToRoute($route1, [$baseLocation, $amsterdamLocation, $rotterdamLocation, $baseLocation]);
        
        // Create routes for the high capacity locations
        $routeCounter = 1;
        $startTimes = ['07:30', '08:00', '08:30', '09:00', '09:30'];
        
        foreach ($highCapacityLocations as $location) {
            // Create two routes for each high capacity location
            for ($i = 0; $i < 2; $i++) {
                $routeCounter++;
                $routeName = $location->city . ' Route ' . ($i + 1);
                
                $route = Route::create([
                    'name' => $routeName,
                    'description' => 'Route ' . ($i + 1) . ' through ' . $location->name,
                    'start_time' => $startTimes[array_rand($startTimes)]
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
     * Add locations to a route with proper ordering and time calculations
     */
    private function addLocationsToRoute($route, $locations)
    {
        $order = 1;
        $currentTime = Carbon::parse($route->start_time ?? '08:00');
        $previousLocation = null;
        
        foreach ($locations as $location) {
            // Calculate travel time from previous location (if any)
            $travelTime = null;
            if ($previousLocation) {
                // Calculate simple travel time based on distance (30 minutes per 50km)
                $distance = $this->calculateDistance(
                    $previousLocation->latitude, 
                    $previousLocation->longitude, 
                    $location->latitude, 
                    $location->longitude
                );
                
                $travelTime = ceil($distance / 50 * 30); // minutes
                $currentTime = $currentTime->copy()->addMinutes($travelTime);
            }
            
            // Ensure arrival time respects the location's time window
            if ($location->begin_time) {
                $earliestArrival = Carbon::parse($location->begin_time);
                if ($currentTime->lt($earliestArrival)) {
                    $currentTime = $earliestArrival->copy();
                }
            }
            
            // Set arrival time
            $arrivalTime = $currentTime->format('H:i');
            
            // Calculate completion time
            $completionMinutes = $location->completion_minutes ?? 30; // Default 30 minutes if not specified
            $completionTime = $currentTime->copy()->addMinutes($completionMinutes)->format('H:i');
            
            // Update current time to after completion
            $currentTime = $currentTime->copy()->addMinutes($completionMinutes);
            
            DB::table('route_location')->insert([
                'route_id' => $route->id,
                'location_id' => $location->id,
                'order' => $order,
                'arrival_time' => $arrivalTime,
                'completion_time' => $completionTime,
                'travel_time' => $travelTime,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $order++;
            $previousLocation = $location;
        }
    }
    
    /**
     * Calculate distance between two points in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $R = 6371; // Earth's radius in km
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);

        $a = sin($Δφ/2)**2 + cos($φ1)*cos($φ2)*sin($Δλ/2)**2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
