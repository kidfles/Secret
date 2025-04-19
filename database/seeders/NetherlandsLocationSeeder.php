<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class NetherlandsLocationSeeder extends Seeder
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
        
        // Define tile types - using database format
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

        // List of diverse locations throughout Netherlands
        $locations = [
            // Amsterdam (Noord-Holland)
            [
                'name' => 'Zuidas Business District',
                'street' => 'Gustav Mahlerlaan',
                'house_number' => '10',
                'city' => 'Amsterdam',
                'postal_code' => '1082 PP',
                'latitude' => 52.337934,
                'longitude' => 4.874371,
                'person_capacity' => 3,
                'tegels' => 25,
                'tegels_type' => 'pix100',
                'time_window_index' => 2, // Full day
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
                'person_capacity' => 4,
                'tegels' => 18,
                'tegels_type' => 'vlakled',
                'time_window_index' => 0, // Morning only
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
                'person_capacity' => 2,
                'tegels' => 15,
                'tegels_type' => 'pix25',
                'time_window_index' => 1, // Afternoon only
            ],
            
            // The Hague (Zuid-Holland)
            [
                'name' => 'Binnenhof',
                'street' => 'Binnenhof',
                'house_number' => '1',
                'city' => 'Den Haag',
                'postal_code' => '2513 AA',
                'latitude' => 52.079585,
                'longitude' => 4.312422,
                'person_capacity' => 3,
                'tegels' => 22,
                'tegels_type' => 'patroon',
                'time_window_index' => 2, // Full day
            ],
            
            // Eindhoven (Noord-Brabant)
            [
                'name' => 'High Tech Campus',
                'street' => 'High Tech Campus',
                'house_number' => '1',
                'city' => 'Eindhoven',
                'postal_code' => '5656 AE',
                'latitude' => 51.411891,
                'longitude' => 5.458335,
                'person_capacity' => 4,
                'tegels' => 30,
                'tegels_type' => 'pix100',
                'time_window_index' => 3, // Early morning
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
                'person_capacity' => 2,
                'tegels' => 12,
                'tegels_type' => 'pix25',
                'time_window_index' => 4, // Late afternoon
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
                'person_capacity' => 3,
                'tegels' => 24,
                'tegels_type' => 'vlakled',
                'time_window_index' => 5, // No constraints
            ],
            
            // Enschede (Overijssel)
            [
                'name' => 'Universiteit Twente',
                'street' => 'Drienerlolaan',
                'house_number' => '5',
                'city' => 'Enschede',
                'postal_code' => '7522 NB',
                'latitude' => 52.239524,
                'longitude' => 6.852567,
                'person_capacity' => 4,
                'tegels' => 20,
                'tegels_type' => 'patroon',
                'time_window_index' => 2, // Full day
            ],
            
            // Leeuwarden (Friesland)
            [
                'name' => 'Oldehove',
                'street' => 'Oldehoofsterkerkhof',
                'house_number' => '1',
                'city' => 'Leeuwarden',
                'postal_code' => '8911 DH',
                'latitude' => 53.201748,
                'longitude' => 5.783729,
                'person_capacity' => 2,
                'tegels' => 14,
                'tegels_type' => 'pix25',
                'time_window_index' => 0, // Morning only
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
                'person_capacity' => 3,
                'tegels' => 16,
                'tegels_type' => 'vlakled',
                'time_window_index' => 1, // Afternoon only
            ],
            
            // Arnhem (Gelderland)
            [
                'name' => 'Burgers Zoo',
                'street' => 'Antoon van Hooffplein',
                'house_number' => '1',
                'city' => 'Arnhem',
                'postal_code' => '6816 SH',
                'latitude' => 52.005493,
                'longitude' => 5.898607,
                'person_capacity' => 5,
                'tegels' => 28,
                'tegels_type' => 'pix100',
                'time_window_index' => 2, // Full day
            ],
            
            // Assen (Drenthe)
            [
                'name' => 'TT Circuit Assen',
                'street' => 'De Haar',
                'house_number' => '9',
                'city' => 'Assen',
                'postal_code' => '9405 TE',
                'latitude' => 52.961613,
                'longitude' => 6.524936,
                'person_capacity' => 4,
                'tegels' => 22,
                'tegels_type' => 'patroon',
                'time_window_index' => 5, // No constraints
            ],
            
            // Lelystad (Flevoland)
            [
                'name' => 'Batavia Stad',
                'street' => 'Bataviaplein',
                'house_number' => '60',
                'city' => 'Lelystad',
                'postal_code' => '8242 PN',
                'latitude' => 52.517699,
                'longitude' => 5.429462,
                'person_capacity' => 3,
                'tegels' => 18,
                'tegels_type' => 'pix25',
                'time_window_index' => 4, // Late afternoon
            ],
            
            // 's-Hertogenbosch (Noord-Brabant)
            [
                'name' => 'Sint-Janskathedraal',
                'street' => 'Torenstraat',
                'house_number' => '16',
                'city' => 's-Hertogenbosch',
                'postal_code' => '5211 KK',
                'latitude' => 51.686965,
                'longitude' => 5.303642,
                'person_capacity' => 2,
                'tegels' => 15,
                'tegels_type' => 'vlakled',
                'time_window_index' => 3, // Early morning
            ],
            
            // Start/base location - Nederasselt (Gelderland)
            [
                'name' => 'Nederasselt Base',
                'street' => 'Broekstraat',
                'house_number' => '68',
                'city' => 'Nederasselt',
                'postal_code' => '6621 JL',
                'latitude' => 51.762050, 
                'longitude' => 5.765000,
                'person_capacity' => 2,
                'tegels' => 0,
                'tegels_type' => null,
                'time_window_index' => 5, // No constraints
            ],
        ];

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
        
        $this->command->info('Created ' . count($locations) . ' locations spread throughout the Netherlands');
    }
} 