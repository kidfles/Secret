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
        Schema::table('locations', function (Blueprint $table) {
            $table->time('begin_time')->nullable()->comment('Earliest time we can arrive at this location');
            $table->time('end_time')->nullable()->comment('Latest time this location needs to be completed');
            $table->integer('estimated_duration')->nullable()->comment('Estimated time in minutes to complete the location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['begin_time', 'end_time', 'estimated_duration']);
        });
    }
};
