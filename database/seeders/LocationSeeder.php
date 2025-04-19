<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check database driver
        $connection = DB::connection()->getDriverName();
        
        // Clear existing locations
        if ($connection === 'sqlite') {
            DB::table('locations')->delete();
        } else {
            DB::table('locations')->truncate();
        }
        
        // Define tile types - using database format (not display format)
        $tileTypes = ['pix25', 'pix100', 'vlakled', 'patroon'];
        
        // Define time windows (in 24-hour format)
        $timeWindows = [
            // Morning only
            ['08:00', '12:00'],
            // Afternoon only
            ['13:00', '17:00'],
            // Full day
            ['08:00', '17:00'],
            // Early morning
            ['07:00', '10:00'],
            // Late afternoon
            ['15:00', '18:00'],
            // No constraints
            [null, null],
        ];

        // Netherlands cities around Nederasselt (which is the starting point)
        $cities = [
            // City, Postal Code
            ['Nijmegen', '6500'],
            ['Wijchen', '6600'],
            ['Grave', '5360'],
            ['Cuijk', '5430'],
            ['Malden', '6580'],
            ['Molenhoek', '6584'],
            ['Beers', '5437'],
            ['Heumen', '6582'],
            ['Overasselt', '6611'],
            ['Beuningen', '6640'],
            ['Druten', '6650'],
            ['Ewijk', '6644'],
            ['Wijchen', '6605'],
            ['Alverna', '6603'],
            ['Batenburg', '6634'],
            ['Bergharen', '6617'],
            ['Hernen', '6616'],
            ['Leur', '6615'],
            ['Niftrik', '6606'],
        ];

        // List of locations
        $locations = [
            // Near Nijmegen
            [
                'name' => 'Residentie De Hoge Hof',
                'street' => 'Hogelandseweg',
                'house_number' => '88',
                'city' => 'Nijmegen',
                'postal_code' => '6545 AB',
                'latitude' => 51.837525,
                'longitude' => 5.858041,
                'person_capacity' => 3,
                'tegels' => 18,
                'tegels_type' => 'pix100',
                'time_window_index' => 0, // Morning only
            ],
            [
                'name' => 'Appartementen Mariënburg',
                'street' => 'Mariënburg',
                'house_number' => '26',
                'city' => 'Nijmegen',
                'postal_code' => '6511 PS',
                'latitude' => 51.842772,
                'longitude' => 5.866394,
                'person_capacity' => 2,
                'tegels' => 12,
                'tegels_type' => 'pix25',
                'time_window_index' => 2, // Full day
            ],
            [
                'name' => 'Woontoren Nimbus',
                'street' => 'Stieltjesstraat',
                'house_number' => '204',
                'city' => 'Nijmegen',
                'postal_code' => '6512 WR',
                'latitude' => 51.839912,
                'longitude' => 5.856841,
                'person_capacity' => 4,
                'tegels' => 24,
                'tegels_type' => 'vlakled',
                'time_window_index' => 4, // Late afternoon
            ],
            
            // Wijchen area
            [
                'name' => 'De Meeuwse Acker',
                'street' => 'Meeuwse Acker',
                'house_number' => '1445',
                'city' => 'Wijchen',
                'postal_code' => '6605 LN',
                'latitude' => 51.806824,
                'longitude' => 5.725903,
                'person_capacity' => 2,
                'tegels' => 16,
                'tegels_type' => 'pix100',
                'time_window_index' => 1, // Afternoon only
            ],
            [
                'name' => 'Kasteel Wijchen',
                'street' => 'Kasteellaan',
                'house_number' => '9',
                'city' => 'Wijchen',
                'postal_code' => '6602 DE',
                'latitude' => 51.808667,
                'longitude' => 5.725903,
                'person_capacity' => 2,
                'tegels' => 8,
                'tegels_type' => 'patroon',
                'time_window_index' => 5, // No constraints
            ],
            
            // Grave area
            [
                'name' => 'Oldenbarneveldtplein',
                'street' => 'Oldenbarneveldtplein',
                'house_number' => '10',
                'city' => 'Grave',
                'postal_code' => '5361 CL',
                'latitude' => 51.759266,
                'longitude' => 5.737488,
                'person_capacity' => 3,
                'tegels' => 22,
                'tegels_type' => 'vlakled',
                'time_window_index' => 3, // Early morning
            ],
            
            // Cuijk area
            [
                'name' => 'Appartement Centrum Cuijk',
                'street' => 'Grotestraat',
                'house_number' => '45',
                'city' => 'Cuijk',
                'postal_code' => '5431 DH',
                'latitude' => 51.728687,
                'longitude' => 5.877776,
                'person_capacity' => 2,
                'tegels' => 14,
                'tegels_type' => 'pix25',
                'time_window_index' => 2, // Full day
            ],
            
            // Malden area
            [
                'name' => 'Wooncomplex De Horst',
                'street' => 'Broeksingel',
                'house_number' => '1',
                'city' => 'Malden',
                'postal_code' => '6581 HA',
                'latitude' => 51.778894,
                'longitude' => 5.850235,
                'person_capacity' => 3,
                'tegels' => 20,
                'tegels_type' => 'pix100',
                'time_window_index' => 0, // Morning only
            ],
            
            // Overasselt area
            [
                'name' => 'Dorpsplein',
                'street' => 'Hoogstraat',
                'house_number' => '9',
                'city' => 'Overasselt',
                'postal_code' => '6611 BT',
                'latitude' => 51.756966,
                'longitude' => 5.776617,
                'person_capacity' => 2,
                'tegels' => 10,
                'tegels_type' => 'patroon',
                'time_window_index' => 5, // No constraints
            ],
            
            // Beuningen area
            [
                'name' => 'Centrumplein',
                'street' => 'Wilhelminalaan',
                'house_number' => '5',
                'city' => 'Beuningen',
                'postal_code' => '6641 KN',
                'latitude' => 51.860065,
                'longitude' => 5.765903,
                'person_capacity' => 2,
                'tegels' => 15,
                'tegels_type' => 'pix25',
                'time_window_index' => 1, // Afternoon only
            ],
            
            // Druten area
            [
                'name' => 'Woonpark Druten',
                'street' => 'Kattenburg',
                'house_number' => '12',
                'city' => 'Druten',
                'postal_code' => '6651 LA',
                'latitude' => 51.887815,
                'longitude' => 5.609752,
                'person_capacity' => 3,
                'tegels' => 25,
                'tegels_type' => 'vlakled',
                'time_window_index' => 4, // Late afternoon
            ],
            
            // Generate additional random locations
            // These will be added dynamically below
        ];

        // Generate additional locations to have a good dataset
        $additionalLocations = 15;
        for ($i = 0; $i < $additionalLocations; $i++) {
            $cityIndex = rand(0, count($cities) - 1);
            $cityInfo = $cities[$cityIndex];
            $typeIndex = rand(0, count($tileTypes) - 1);
            $timeWindowIndex = rand(0, count($timeWindows) - 1);
            
            // Generate a reasonable number of tiles
            $tileCount = rand(4, 30);
            
            // Random coordinates near the Netherlands (centered around Nederasselt)
            $lat = 51.76 + (rand(-100, 100) / 1000); // Base 51.76 with variation
            $lng = 5.75 + (rand(-100, 100) / 1000);  // Base 5.75 with variation
            
            $locations[] = [
                'name' => 'Locatie ' . ($i + 1),
                'street' => 'Straat',
                'house_number' => (string)rand(1, 100),
                'city' => $cityInfo[0],
                'postal_code' => $cityInfo[1] . ' ' . chr(65 + rand(0, 25)) . chr(65 + rand(0, 25)),
                'latitude' => $lat,
                'longitude' => $lng,
                'person_capacity' => rand(1, 4),
                'tegels' => $tileCount,
                'tegels_type' => $tileTypes[$typeIndex],
                'time_window_index' => $timeWindowIndex,
            ];
        }

        // Now insert all locations
        foreach ($locations as $loc) {
            $timeWindowIndex = $loc['time_window_index'];
            $timeWindow = $timeWindows[$timeWindowIndex];
            
            // Calculate realistic completion time based on tile count and formula
            $baseDuration = 40; // Base duration in minutes
            $additionalTime = ceil($loc['tegels'] * 2); // 2 minutes per tegel, rounded up
            $completionMinutes = $baseDuration + $additionalTime;
            
            // Create the full address
            $address = $loc['street'] . ' ' . $loc['house_number'] . ', ' . $loc['city'];
            
            Location::create([
                'name' => $loc['name'],
                'street' => $loc['street'],
                'house_number' => $loc['house_number'],
                'city' => $loc['city'],
                'postal_code' => $loc['postal_code'],
                'latitude' => $loc['latitude'],
                'longitude' => $loc['longitude'],
                'person_capacity' => $loc['person_capacity'],
                'address' => $address,
                'tegels' => $loc['tegels'],
                'tegels_count' => $loc['tegels'], // Set both for compatibility
                'tegels_type' => $loc['tegels_type'],
                'begin_time' => $timeWindow[0], // Store just the time string
                'end_time' => $timeWindow[1],   // Store just the time string
                'completion_minutes' => $completionMinutes,
            ]);
        }
        
        $this->command->info('Created ' . count($locations) . ' locations with varying tile types and time windows');
    }
} 