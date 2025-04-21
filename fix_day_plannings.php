<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$plannings = DB::table('day_plannings')
    ->where('date', 'like', '2025-04-21%')
    ->orderBy('id')
    ->get();

echo "Found " . count($plannings) . " day plannings for April 21, 2025\n";

if (count($plannings) > 1) {
    // Keep only the first day planning
    $keepId = $plannings[0]->id;
    
    // Delete all others
    $deleteIds = [];
    foreach ($plannings as $index => $planning) {
        if ($index > 0) {
            $deleteIds[] = $planning->id;
        }
    }
    
    if (!empty($deleteIds)) {
        $deleted = DB::table('day_plannings')
            ->whereIn('id', $deleteIds)
            ->delete();
        
        echo "Deleted " . $deleted . " duplicate day plannings. Keeping planning with ID: " . $keepId . "\n";
    }
}

// Now check for any date/timezone issues
echo "\nChecking date formatting:\n";
$date = '2025-04-21';

// Original format
echo "Original date: " . $date . "\n";

// Parse with explicit timezone
$carbonDate = \Carbon\Carbon::parse($date, 'Europe/Amsterdam');
echo "Carbon parsed with Amsterdam timezone: " . $carbonDate->format('Y-m-d H:i:s') . "\n";

// Format for display
$formattedDate = $carbonDate->format('d-m-Y');
echo "Formatted for display: " . $formattedDate . "\n";

// Now check the RouteOptimizer date display code
echo "\nSimulating RouteOptimizer date formatting:\n";
// This is the problem line in the controller
$selectedDate = '2025-04-21';
$formattedDate = \Carbon\Carbon::parse($selectedDate)->format('d-m-Y');
echo "Controller formatted date: " . $formattedDate . "\n";

// Tomorrow
$tomorrow = \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d');
echo "Tomorrow: " . $tomorrow . "\n";
$tomorrowFormatted = \Carbon\Carbon::parse($tomorrow)->format('d-m-Y');
echo "Tomorrow formatted: " . $tomorrowFormatted . "\n"; 