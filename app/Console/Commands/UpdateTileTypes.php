<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTileTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-tile-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates locations with "onbekend" tile types to the standard types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating tile types...');
        
        // Get locations with 'onbekend' or empty tile types but have tiles
        $locations = Location::whereNull('tegels_type')
            ->orWhere('tegels_type', 'onbekend')
            ->where(function($query) {
                $query->where('tegels', '>', 0)
                    ->orWhere('tegels_count', '>', 0);
            })
            ->get();
            
        $this->info("Found {$locations->count()} locations with missing or unknown tile types.");
        
        if ($locations->count() == 0) {
            $this->info('No locations to update.');
            return;
        }
        
        // Define the standard types
        $tileTypes = ['Pix 25', 'Pix 100', 'Vlakled', 'Patroon'];
        
        // For each location, assign a type based on value or ask the user
        $count = 0;
        foreach ($locations as $location) {
            $this->info("Location: {$location->name}, {$location->address}");
            $this->info("Tiles: " . ($location->tegels ?? $location->tegels_count));
            
            // Display options to the user
            $type = $this->choice(
                'Select the tile type for this location:',
                $tileTypes,
                0 // Default to Pix 25
            );
            
            // Update the location
            $location->tegels_type = $type;
            $location->save();
            $count++;
            
            $this->info("Updated tile type to: {$type}");
            $this->newLine();
        }
        
        $this->info("Successfully updated {$count} locations.");
    }
} 