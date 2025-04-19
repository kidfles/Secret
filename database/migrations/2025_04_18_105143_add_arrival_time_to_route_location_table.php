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
        Schema::table('route_location', function (Blueprint $table) {
            $table->time('arrival_time')->nullable()->comment('Estimated arrival time at this location');
            $table->time('completion_time')->nullable()->comment('Estimated completion time for this location');
            $table->integer('travel_time')->nullable()->comment('Estimated travel time in minutes from previous location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_location', function (Blueprint $table) {
            $table->dropColumn(['arrival_time', 'completion_time', 'travel_time']);
        });
    }
};
