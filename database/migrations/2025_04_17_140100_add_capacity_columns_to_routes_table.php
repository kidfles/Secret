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
            if (!Schema::hasColumn('routes', 'person_capacity')) {
                $table->integer('person_capacity')->default(2)->after('description');
            }
            if (!Schema::hasColumn('routes', 'capacity')) {
                $table->integer('capacity')->default(3)->after('person_capacity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['person_capacity', 'capacity']);
        });
    }
}; 