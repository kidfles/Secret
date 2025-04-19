<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing locations
        Location::truncate();

        // Amsterdam
        Location::create([
            'name' => 'Amsterdam Centraal',
            'street' => 'Stationsplein',
            'house_number' => '1',
            'postal_code' => '1012 AB',
            'city' => 'Amsterdam',
            'latitude' => 52.3791,
            'longitude' => 4.9003,
            'address' => 'Stationsplein 1, 1012 AB Amsterdam',
            'person_capacity' => 3,
            'tegels' => 30,
            'begin_time' => '09:00',
            'end_time' => '17:00',
            'completion_minutes' => 90
        ]);

        // Rotterdam
        Location::create([
            'name' => 'Rotterdam Markthal',
            'street' => 'Dominee Jan Scharpstraat',
            'house_number' => '298',
            'postal_code' => '3011 GZ',
            'city' => 'Rotterdam',
            'latitude' => 51.9200,
            'longitude' => 4.4875,
            'address' => 'Dominee Jan Scharpstraat 298, 3011 GZ Rotterdam',
            'person_capacity' => 4,
            'tegels' => 25,
            'begin_time' => '08:30',
            'end_time' => '16:30',
            'completion_minutes' => 75
        ]);

        // Den Haag
        Location::create([
            'name' => 'Binnenhof',
            'street' => 'Binnenhof',
            'house_number' => '1',
            'postal_code' => '2513 AA',
            'city' => 'Den Haag',
            'latitude' => 52.0797,
            'longitude' => 4.3122,
            'address' => 'Binnenhof 1, 2513 AA Den Haag',
            'person_capacity' => 2,
            'tegels' => 15,
            'begin_time' => '10:00',
            'end_time' => '15:00',
            'completion_minutes' => 60
        ]);

        // Utrecht
        Location::create([
            'name' => 'Domtoren',
            'street' => 'Domplein',
            'house_number' => '1',
            'postal_code' => '3512 JC',
            'city' => 'Utrecht',
            'latitude' => 52.0907,
            'longitude' => 5.1214,
            'address' => 'Domplein 1, 3512 JC Utrecht',
            'person_capacity' => 3,
            'tegels' => 45,
            'begin_time' => '09:30',
            'end_time' => '17:30',
            'completion_minutes' => 120
        ]);

        // Eindhoven
        Location::create([
            'name' => 'Philips Stadion',
            'street' => 'Frederiklaan',
            'house_number' => '10',
            'postal_code' => '5616 NH',
            'city' => 'Eindhoven',
            'latitude' => 51.4416,
            'longitude' => 5.4697,
            'address' => 'Frederiklaan 10, 5616 NH Eindhoven',
            'person_capacity' => 2,
            'tegels' => 20,
            'begin_time' => '11:00',
            'end_time' => '19:00',
            'completion_minutes' => 70
        ]);

        // Groningen
        Location::create([
            'name' => 'Martinitoren',
            'street' => 'Martinikerkhof',
            'house_number' => '1',
            'postal_code' => '9712 JG',
            'city' => 'Groningen',
            'latitude' => 53.2194,
            'longitude' => 6.5665,
            'address' => 'Martinikerkhof 1, 9712 JG Groningen',
            'person_capacity' => 2,
            'tegels' => 35,
            'begin_time' => '08:00',
            'end_time' => '16:00',
            'completion_minutes' => 100
        ]);

        // Maastricht
        Location::create([
            'name' => 'Vrijthof',
            'street' => 'Vrijthof',
            'house_number' => '1',
            'postal_code' => '6211 LD',
            'city' => 'Maastricht',
            'latitude' => 50.8483,
            'longitude' => 5.6889,
            'address' => 'Vrijthof 1, 6211 LD Maastricht',
            'person_capacity' => 4,
            'tegels' => 28,
            'begin_time' => '09:00',
            'end_time' => '18:00',
            'completion_minutes' => 80
        ]);

        // Zwolle
        Location::create([
            'name' => 'Grote Markt',
            'street' => 'Grote Markt',
            'house_number' => '1',
            'postal_code' => '8011 LW',
            'city' => 'Zwolle',
            'latitude' => 52.5168,
            'longitude' => 6.0830,
            'address' => 'Grote Markt 1, 8011 LW Zwolle',
            'person_capacity' => 2,
            'tegels' => 22,
            'begin_time' => '10:30',
            'end_time' => '16:30',
            'completion_minutes' => 65
        ]);

        // Arnhem
        Location::create([
            'name' => 'Burgers Zoo',
            'street' => 'Antoon van Hooffplein',
            'house_number' => '1',
            'postal_code' => '6816 SH',
            'city' => 'Arnhem',
            'latitude' => 52.0055,
            'longitude' => 5.8987,
            'address' => 'Antoon van Hooffplein 1, 6816 SH Arnhem',
            'person_capacity' => 5,
            'tegels' => 60,
            'begin_time' => '09:00',
            'end_time' => '18:00',
            'completion_minutes' => 150
        ]);

        // Nijmegen
        Location::create([
            'name' => 'Valkhof Museum',
            'street' => 'Kelfkensbos',
            'house_number' => '59',
            'postal_code' => '6511 TB',
            'city' => 'Nijmegen',
            'latitude' => 51.8491,
            'longitude' => 5.8694,
            'address' => 'Kelfkensbos 59, 6511 TB Nijmegen',
            'person_capacity' => 2,
            'tegels' => 18,
            'begin_time' => '10:00',
            'end_time' => '17:00',
            'completion_minutes' => 60
        ]);

        // Nederasselt (Base/Home location)
        Location::create([
            'name' => 'Nederasselt Base',
            'street' => 'Hollestraat',
            'house_number' => '25',
            'postal_code' => '6621 JL',
            'city' => 'Nederasselt',
            'latitude' => 51.7620,
            'longitude' => 5.7650,
            'address' => 'Hollestraat 25, 6621 JL Nederasselt',
            'person_capacity' => 2,
            'tegels' => 10,
            'begin_time' => '07:00',
            'end_time' => '20:00',
            'completion_minutes' => 40
        ]);
    }
} 