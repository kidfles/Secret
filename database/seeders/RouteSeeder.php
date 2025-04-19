<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Route;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check database driver
        $connection = DB::connection()->getDriverName();
        
        // Clear existing routes and pivot data
        if ($connection === 'sqlite') {
            DB::table('route_location')->delete();
            DB::table('routes')->delete();
        } else {
            DB::table('route_location')->truncate();
            DB::table('routes')->truncate();
        }
        
        // Create 4 routes
        $routeNames = [
            'Route A', 'Route B', 'Route C', 'Route D'
        ];
        
        $routes = [];
        foreach ($routeNames as $name) {
            // Check which columns exist in the routes table
            $hasCapacity = Schema::hasColumn('routes', 'capacity');
            $hasMaxDuration = Schema::hasColumn('routes', 'max_duration_minutes');
            
            // Build route data based on available columns
            $routeData = [
                'name' => $name,
                'start_time' => '08:00:00',
            ];
            
            // Add optional columns if they exist
            if ($hasCapacity) {
                $routeData['capacity'] = 100; // Maximum capacity per route
            }
            
            if ($hasMaxDuration) {
                $routeData['max_duration_minutes'] = 480; // 8 hour workday
            }
            
            $routes[] = Route::create($routeData);
        }
        
        // Fetch all locations
        $locations = Location::all();
        
        // Skip assignment if no locations
        if ($locations->isEmpty()) {
            $this->command->info('No locations found to assign to routes');
            return;
        }
        
        // Group locations by city for more realistic routes
        $locationsByCity = $locations->groupBy('city');
        
        // Assign locations to routes - try to group by city when possible
        $routeIndex = 0;
        $locationOrder = 1;
        $pivotData = [];
        $routeStats = [];
        
        // Initialize route stats
        foreach ($routes as $route) {
            $routeStats[$route->id] = [
                'total_tiles' => 0,
                'location_count' => 0,
                'total_time' => 0,
            ];
        }
        
        // Base coordinates for Nederasselt (starting point for all routes)
        $startLat = 51.7620;
        $startLng = 5.7650;
        
        // Assign each city's locations to the routes in a round-robin fashion
        foreach ($locationsByCity as $city => $cityLocations) {
            foreach ($cityLocations as $location) {
                $route = $routes[$routeIndex % count($routes)];
                
                // Calculate travel time from previous location or start
                if ($locationOrder === 1) {
                    // First location in route - calculate from Nederasselt
                    $travelTimeMinutes = $this->calculateTravelTime($startLat, $startLng, $location->latitude, $location->longitude);
                    $arrivalTime = Carbon::parse($route->start_time)->addMinutes($travelTimeMinutes)->format('H:i:s');
                } else {
                    // Calculate from previous location in this route
                    $prevLocations = collect($pivotData)->where('route_id', $route->id)->sortByDesc('order');
                    
                    if ($prevLocations->count() > 0) {
                        $prevLocation = $prevLocations->first();
                        $prevLoc = $locations->firstWhere('id', $prevLocation['location_id']);
                        $prevCompletionTime = $prevLocation['completion_time'];
                        
                        // Calculate travel time from previous location
                        $travelTimeMinutes = $this->calculateTravelTime(
                            $prevLoc->latitude, 
                            $prevLoc->longitude, 
                            $location->latitude, 
                            $location->longitude
                        );
                        
                        $arrivalTime = Carbon::parse($prevCompletionTime)->addMinutes($travelTimeMinutes)->format('H:i:s');
                    } else {
                        // Fallback if no previous location found
                        $travelTimeMinutes = $this->calculateTravelTime($startLat, $startLng, $location->latitude, $location->longitude);
                        $arrivalTime = Carbon::parse($route->start_time)->addMinutes($travelTimeMinutes)->format('H:i:s');
                    }
                }
                
                // Calculate completion time
                $completionTimeMinutes = $location->completion_minutes ?? $location->getCompletionTimeAttribute();
                $completionTime = Carbon::parse($arrivalTime)->addMinutes($completionTimeMinutes)->format('H:i:s');
                
                // Create pivot data
                $pivotData[] = [
                    'route_id' => $route->id,
                    'location_id' => $location->id,
                    'order' => $locationOrder,
                    'arrival_time' => $arrivalTime,
                    'completion_time' => $completionTime,
                    'travel_time' => $travelTimeMinutes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Update route stats
                $routeStats[$route->id]['total_tiles'] += $location->tegels ?? $location->tegels_count ?? 0;
                $routeStats[$route->id]['location_count']++;
                $routeStats[$route->id]['total_time'] += $completionTimeMinutes;
                
                $locationOrder++;
                $routeIndex++;
            }
        }
        
        // Insert all pivot data
        DB::table('route_location')->insert($pivotData);
        
        // Output stats
        $this->command->info('Created ' . count($routes) . ' routes');
        
        foreach ($routes as $route) {
            $stats = $routeStats[$route->id];
            $this->command->info("{$route->name}: {$stats['location_count']} locations, {$stats['total_tiles']} tiles, {$stats['total_time']} minutes total work");
        }
    }
    
    /**
     * Calculate travel time between two coordinates
     * Simple implementation using distance and average speed
     */
    private function calculateTravelTime($lat1, $lng1, $lat2, $lng2, $avgSpeedKmh = 50)
    {
        // Calculate distance in kilometers using Haversine formula
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        // Calculate time in minutes
        $timeHours = $distance / $avgSpeedKmh;
        $timeMinutes = ceil($timeHours * 60);
        
        // Ensure minimum travel time
        return max(5, $timeMinutes);
    }
}
