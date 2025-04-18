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
            $table->integer('tegels_count')->default(0)->after('person_capacity');
            $table->enum('tegels_type', ['pix100', 'pix25', 'vlakled', 'patroon'])->nullable()->after('tegels_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('tegels_count');
            $table->dropColumn('tegels_type');
        });
    }
};
