<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            // Check if the column doesn't already exist
            if (!Schema::hasColumn('routes', 'max_duration_minutes')) {
                $table->integer('max_duration_minutes')->default(480)->after('start_time')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            if (Schema::hasColumn('routes', 'max_duration_minutes')) {
                $table->dropColumn('max_duration_minutes');
            }
        });
    }
}; 