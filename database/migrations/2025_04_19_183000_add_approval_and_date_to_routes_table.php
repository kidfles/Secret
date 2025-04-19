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
            $table->date('scheduled_date')->nullable()->after('name');
            $table->boolean('is_approved')->default(false)->after('scheduled_date');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->string('approved_by')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('scheduled_date');
            $table->dropColumn('is_approved');
            $table->dropColumn('approved_at');
            $table->dropColumn('approved_by');
        });
    }
}; 