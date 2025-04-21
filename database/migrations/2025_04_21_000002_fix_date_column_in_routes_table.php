<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a new table with the correct structure
        Schema::create('routes_new', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->integer('person_capacity')->nullable();
            $table->integer('capacity')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('max_duration_minutes')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->dateTime('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamps();
        });

        // Copy data from old table to new table
        $routes = DB::table('routes')->get();
        foreach ($routes as $route) {
            DB::table('routes_new')->insert([
                'id' => $route->id,
                'name' => $route->name,
                'description' => $route->description,
                'person_capacity' => $route->person_capacity ?? null,
                'capacity' => $route->capacity ?? null,
                'start_time' => $route->start_time ?? null,
                'max_duration_minutes' => $route->max_duration_minutes ?? null,
                'scheduled_date' => $route->scheduled_date ?? null,
                'is_approved' => $route->is_approved ?? false,
                'approved_at' => $route->approved_at ?? null,
                'approved_by' => $route->approved_by ?? null,
                'date' => $route->date ?? null,
                'created_at' => $route->created_at,
                'updated_at' => $route->updated_at,
            ]);
        }

        // Drop the old table
        Schema::dropIfExists('routes');

        // Rename the new table to the correct name
        Schema::rename('routes_new', 'routes');

        // Recreate foreign keys for route_location
        Schema::table('route_location', function (Blueprint $table) {
            // First remove the existing foreign key
            $table->dropForeign(['route_id']);
            
            // Then add it back
            $table->foreign('route_id')
                ->references('id')
                ->on('routes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not implemented since this is a fix
    }
}; 