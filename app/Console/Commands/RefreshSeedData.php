<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class RefreshSeedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-seed-data {--fresh : Migrate fresh before seeding} {--locations-only : Only seed locations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes all seed data or only locations as specified';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data refresh process...');

        // Check if we need to run migrations
        if ($this->option('fresh')) {
            $this->info('Running fresh migrations...');
            Artisan::call('migrate:fresh');
            $this->info('Migrations complete.');
        }

        // Get the current database connection
        $connection = DB::connection()->getDriverName();

        // Disable foreign key checks during seeding based on the database driver
        $this->info('Disabling foreign key constraints...');
        if ($connection === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Run the location seeder
        $this->info('Seeding locations...');
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\LocationSeeder',
            '--force' => true
        ]);
        $this->info('Locations seeded successfully.');

        // If not locations only, seed routes as well
        if (!$this->option('locations-only')) {
            $this->info('Seeding routes...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\RouteSeeder',
                '--force' => true
            ]);
            $this->info('Routes seeded successfully.');
        }

        // Re-enable foreign key checks
        $this->info('Re-enabling foreign key constraints...');
        if ($connection === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Clear all caches
        $this->info('Clearing caches...');
        Artisan::call('cache:clear');
        
        $this->info('Data refresh complete!');
        
        return Command::SUCCESS;
    }
} 