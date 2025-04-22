<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Route;
use App\Models\DayPlanning;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TodayPlanningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get today's date in Y-m-d format
        $today = Carbon::today()->format('Y-m-d');
        
        // Create day planning for today
        $dayPlanning = DayPlanning::updateOrCreate(
            ['date' => $today],
            [
                'notes' => 'Autogegenereerde planning voor ' . Carbon::today()->format('d-m-Y') . 
                           '. 13 locaties door heel Nederland verspreid over 3 routes.'
            ]
        );
        
        // Create locations
        $locations = $this->createLocations();
        
        // Create routes for today
        $routes = $this->createRoutes($today);
        
        // Assign locations to routes
        $this->assignLocationsToRoutes($locations, $routes);
        
        $this->command->info('Successfully created day planning for today with 13 locations across the Netherlands');
    }
    
    /**
     * Create 13 locations across the Netherlands
     */
    private function createLocations(): array
    {
        // Dutch provinces
        $provinces = [
            'Noord-Holland', 'Zuid-Holland', 'Utrecht', 'Gelderland', 'Overijssel',
            'Drenthe', 'Groningen', 'Friesland', 'Flevoland', 'Noord-Brabant',
            'Limburg', 'Zeeland'
        ];
        
        // Define location data for each province
        $locationData = [
            // Amsterdam (Noord-Holland)
            [
                'name' => 'Cantorgebouw Amsterdam',
                'street' => 'Gustav Mahlerlaan',
                'house_number' => '10',
                'city' => 'Amsterdam',
                'postal_code' => '1082 PP',
                'latitude' => 52.338934,
                'longitude' => 4.874371,
                'tegels' => 32,
                'completion_minutes' => 60,
                'begin_time' => '08:00',
                'end_time' => '17:00',
                'province' => 'Noord-Holland'
            ],
            
            // Rotterdam (Zuid-Holland)
            [
                'name' => 'Markthal Rotterdam',
                'street' => 'Dominee Jan Scharpstraat',
                'house_number' => '298',
                'city' => 'Rotterdam',
                'postal_code' => '3011 GZ',
                'latitude' => 51.920058,
                'longitude' => 4.487472,
                'tegels' => 40,
                'completion_minutes' => 90,
                'begin_time' => '09:00',
                'end_time' => '16:00',
                'province' => 'Zuid-Holland'
            ],
            
            // Utrecht (Utrecht)
            [
                'name' => 'Utrecht Science Park',
                'street' => 'Heidelberglaan',
                'house_number' => '8',
                'city' => 'Utrecht',
                'postal_code' => '3584 CS',
                'latitude' => 52.085646,
                'longitude' => 5.174714,
                'tegels' => 25,
                'completion_minutes' => 45,
                'begin_time' => '08:30',
                'end_time' => '17:30',
                'province' => 'Utrecht'
            ],
            
            // Arnhem (Gelderland)
            [
                'name' => 'Burgers Zoo',
                'street' => 'Antoon van Hooffplein',
                'house_number' => '1',
                'city' => 'Arnhem',
                'postal_code' => '6816 SH',
                'latitude' => 52.011441,
                'longitude' => 5.907778,
                'tegels' => 35,
                'completion_minutes' => 70,
                'begin_time' => '10:00',
                'end_time' => '18:00',
                'province' => 'Gelderland'
            ],
            
            // Zwolle (Overijssel)
            [
                'name' => 'Stadhuis Zwolle',
                'street' => 'Grote Kerkplein',
                'house_number' => '15',
                'city' => 'Zwolle',
                'postal_code' => '8011 PK',
                'latitude' => 52.512665,
                'longitude' => 6.091819,
                'tegels' => 28,
                'completion_minutes' => 60,
                'begin_time' => '08:00',
                'end_time' => '16:00',
                'province' => 'Overijssel'
            ],
            
            // Assen (Drenthe)
            [
                'name' => 'TT Circuit Assen',
                'street' => 'De Haar',
                'house_number' => '9',
                'city' => 'Assen',
                'postal_code' => '9405 TE',
                'latitude' => 52.961667,
                'longitude' => 6.523333,
                'tegels' => 45,
                'completion_minutes' => 120,
                'begin_time' => '09:00',
                'end_time' => '18:00',
                'province' => 'Drenthe'
            ],
            
            // Groningen (Groningen)
            [
                'name' => 'Groninger Museum',
                'street' => 'Museumeiland',
                'house_number' => '1',
                'city' => 'Groningen',
                'postal_code' => '9711 ME',
                'latitude' => 53.212421,
                'longitude' => 6.564382,
                'tegels' => 30,
                'completion_minutes' => 75,
                'begin_time' => '10:00',
                'end_time' => '17:00',
                'province' => 'Groningen'
            ],
            
            // Leeuwarden (Friesland)
            [
                'name' => 'Fries Museum',
                'street' => 'Wilhelminaplein',
                'house_number' => '92',
                'city' => 'Leeuwarden',
                'postal_code' => '8911 BS',
                'latitude' => 53.2013,
                'longitude' => 5.7928,
                'tegels' => 22,
                'completion_minutes' => 55,
                'begin_time' => '09:30',
                'end_time' => '17:30',
                'province' => 'Friesland'
            ],
            
            // Lelystad (Flevoland)
            [
                'name' => 'Batavialand',
                'street' => 'Oostvaardersdijk',
                'house_number' => '113',
                'city' => 'Lelystad',
                'postal_code' => '8242 PA',
                'latitude' => 52.5183,
                'longitude' => 5.4306,
                'tegels' => 18,
                'completion_minutes' => 40,
                'begin_time' => '10:00',
                'end_time' => '16:00',
                'province' => 'Flevoland'
            ],
            
            // Eindhoven (Noord-Brabant)
            [
                'name' => 'Strijp-S',
                'street' => 'Torenallee',
                'house_number' => '20',
                'city' => 'Eindhoven',
                'postal_code' => '5617 BC',
                'latitude' => 51.4486,
                'longitude' => 5.4564,
                'tegels' => 36,
                'completion_minutes' => 80,
                'begin_time' => '08:00',
                'end_time' => '17:00',
                'province' => 'Noord-Brabant'
            ],
            
            // Maastricht (Limburg)
            [
                'name' => 'Vrijthof',
                'street' => 'Vrijthof',
                'house_number' => '34',
                'city' => 'Maastricht',
                'postal_code' => '6211 LD',
                'latitude' => 50.848335,
                'longitude' => 5.689092,
                'tegels' => 42,
                'completion_minutes' => 100,
                'begin_time' => '09:00',
                'end_time' => '18:00',
                'province' => 'Limburg'
            ],
            
            // Middelburg (Zeeland)
            [
                'name' => 'Abdij van Middelburg',
                'street' => 'Abdijplein',
                'house_number' => '1',
                'city' => 'Middelburg',
                'postal_code' => '4331 BK',
                'latitude' => 51.495889,
                'longitude' => 3.610998,
                'tegels' => 20,
                'completion_minutes' => 50,
                'begin_time' => '10:00',
                'end_time' => '16:00',
                'province' => 'Zeeland'
            ],
            
            // Den Haag (Zuid-Holland)
            [
                'name' => 'Binnenhof',
                'street' => 'Binnenhof',
                'house_number' => '1',
                'city' => 'Den Haag',
                'postal_code' => '2513 AA',
                'latitude' => 52.079585,
                'longitude' => 4.312422,
                'tegels' => 38,
                'completion_minutes' => 85,
                'begin_time' => '09:00',
                'end_time' => '17:00',
                'province' => 'Zuid-Holland'
            ],
        ];
        
        // First, clear existing locations for data consistency
        DB::table('locations')->truncate();
        
        // Create locations
        $createdLocations = [];
        foreach ($locationData as $data) {
            $createdLocations[] = Location::create([
                'name' => $data['name'],
                'street' => $data['street'],
                'house_number' => $data['house_number'],
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'address' => $data['street'] . ' ' . $data['house_number'] . ', ' . $data['postal_code'] . ' ' . $data['city'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'tegels' => $data['tegels'],
                'completion_minutes' => $data['completion_minutes'],
                'begin_time' => $data['begin_time'],
                'end_time' => $data['end_time'],
            ]);
        }
        
        return $createdLocations;
    }
    
    /**
     * Create 3 routes for today's planning
     */
    private function createRoutes($date): array
    {
        // Clear existing routes
        DB::table('route_location')->truncate();
        DB::table('routes')->truncate();
        
        $routeNames = [
            'Noord Nederland', 
            'Midden Nederland',
            'Zuid Nederland'
        ];
        
        $startTimes = [
            '08:00:00',
            '08:30:00',
            '09:00:00',
        ];
        
        $createdRoutes = [];
        
        for ($i = 0; $i < 3; $i++) {
            $createdRoutes[] = Route::create([
                'name' => $routeNames[$i],
                'start_time' => $startTimes[$i],
                'date' => $date,
            ]);
        }
        
        return $createdRoutes;
    }
    
    /**
     * Assign locations to routes based on geographical proximity
     */
    private function assignLocationsToRoutes(array $locations, array $routes): void
    {
        // Group locations by region
        $northLocations = [];
        $centralLocations = [];
        $southLocations = [];
        
        foreach ($locations as $location) {
            // North: Groningen, Friesland, Drenthe, North Overijssel
            if ($location->latitude > 52.4) {
                $northLocations[] = $location;
            }
            // South: Limburg, Noord-Brabant, Zeeland
            else if ($location->latitude < 51.8) {
                $southLocations[] = $location;
            }
            // Central: everything else
            else {
                $centralLocations[] = $location;
            }
        }
        
        // Get north, central and south routes
        $northRoute = $routes[0];
        $centralRoute = $routes[1];
        $southRoute = $routes[2];
        
        // Assign locations to routes
        $this->assignLocationsToRoute($northLocations, $northRoute);
        $this->assignLocationsToRoute($centralLocations, $centralRoute);
        $this->assignLocationsToRoute($southLocations, $southRoute);
    }
    
    /**
     * Assign a set of locations to a single route
     */
    private function assignLocationsToRoute(array $locations, Route $route): void
    {
        $order = 1;
        $pivotData = [];
        
        foreach ($locations as $location) {
            // For each location, create pivot data
            $pivotData = [
                'order' => $order,
                'arrival_time' => Carbon::parse($route->start_time)->addMinutes(30 * ($order - 1))->format('H:i:s'),
                'completion_time' => Carbon::parse($route->start_time)->addMinutes(30 * ($order - 1) + $location->completion_minutes)->format('H:i:s'),
                'travel_time' => 30 // Estimated travel time in minutes
            ];
            
            // Attach location to route with pivot data
            $route->locations()->attach($location->id, $pivotData);
            
            $order++;
        }
    }
} 