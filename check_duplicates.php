<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$plannings = DB::table('day_plannings')
    ->where('date', 'like', '2025-04-21%')
    ->get();

echo "Found " . count($plannings) . " day plannings for April 21, 2025:\n";

foreach ($plannings as $planning) {
    echo "ID: " . $planning->id . ", Date: " . $planning->date . "\n";
    
    // Get routes for this planning
    $dateColumn = Schema::hasColumn('routes', 'date') ? 'date' : 'scheduled_date';
    $routes = DB::table('routes')
        ->where($dateColumn, $planning->date)
        ->get();
    
    echo "  Routes for this planning: " . count($routes) . "\n";
    if (count($routes) > 0) {
        foreach ($routes as $route) {
            echo "    Route ID: " . $route->id . ", Name: " . $route->name . "\n";
        }
    }
    
    echo "\n";
}

// Get date in RouteOptimizerController for this date
$selectedDate = '2025-04-21';
$formattedDate = \Carbon\Carbon::parse($selectedDate)->format('d-m-Y');
echo "Formatted date for display: " . $formattedDate . "\n";

// Add one day to check if there's an issue with date formatting
$tomorrow = \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d');
$tomorrowFormatted = \Carbon\Carbon::parse($tomorrow)->format('d-m-Y');
echo "Formatted date for tomorrow: " . $tomorrowFormatted . "\n"; 