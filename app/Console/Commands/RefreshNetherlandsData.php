<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class RefreshNetherlandsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-netherlands-data {--fresh : Migrate fresh before seeding} {--routes-only : Only regenerate routes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes data with locations spread throughout the Netherlands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Netherlands data refresh process...');

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

        // If not routes only, seed locations first
        if (!$this->option('routes-only')) {
            $this->info('Seeding Netherlands locations...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\NetherlandsLocationSeeder',
                '--force' => true
            ]);
            $this->info('Netherlands locations seeded successfully.');
        }

        // Always seed routes
        $this->info('Seeding routes...');
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\RouteSeeder',
            '--force' => true
        ]);
        $this->info('Routes seeded successfully.');

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
        
        $this->info('Netherlands data refresh complete!');
        
        return Command::SUCCESS;
    }
} 