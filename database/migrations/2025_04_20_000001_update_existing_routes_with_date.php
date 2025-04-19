<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy scheduled_date to date for all existing routes
        DB::statement('UPDATE routes SET date = scheduled_date WHERE date IS NULL AND scheduled_date IS NOT NULL');
        
        // For routes without scheduled_date, use created_at date
        DB::statement('UPDATE routes SET date = DATE(created_at) WHERE date IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration can't really be reversed once applied,
        // but we can at least nullify the date field
        DB::statement('UPDATE routes SET date = NULL');
    }
}; 