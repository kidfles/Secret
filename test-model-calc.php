<?php

// This script will calculate the completion times for all locations using the model's method

// Define autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel to allow use of models
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get all locations
use App\Models\Location;
$locations = Location::all();

// Compare the manually set time with what would be calculated
echo "Completion Time Calculation Test\n";
echo str_repeat('-', 100) . "\n";
echo sprintf("%-3s %-20s %-8s %-18s %-18s %-18s\n", 
    "ID", "Name", "Tegels", "Stored Value", "Calculated Value", "Formula Output"
);
echo str_repeat('-', 100) . "\n";

foreach ($locations as $location) {
    // Get the stored value
    $storedValue = $location->completion_minutes;
    
    // Get the calculated value from the accessor method
    $calculatedValue = $location->completion_time;
    
    // Calculate directly with the formula for verification
    $tegelCount = $location->tegels ?? $location->tegels_count ?? 0;
    $formulaOutput = 40 + ceil($tegelCount * 1.5);
    
    echo sprintf("%-3s %-20s %-8s %-18s %-18s %-18s\n", 
        $location->id, 
        substr($location->name, 0, 20), 
        $tegelCount, 
        $storedValue ? "$storedValue minutes" : "NULL", 
        "$calculatedValue minutes", 
        "$formulaOutput minutes"
    );
} 