<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Step 1 of the UUID migration sequence:
     * Add team_id (UUID) to schedule_items table
     */
    public function up(): void
    {
        Schema::table('schedule_items', function (Blueprint $table) {
            // Add team_id column
            $table->uuid('team_id')->after('id')->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_items', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
