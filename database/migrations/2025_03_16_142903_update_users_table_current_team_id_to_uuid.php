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
        // First, drop the current_team_id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_team_id');
        });

        // Then, add it back as a UUID
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('current_team_id')->nullable()->after('remember_token');

            // Add a foreign key constraint to the teams table
            $table->foreign('current_team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, drop the UUID current_team_id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_team_id']);
            $table->dropColumn('current_team_id');
        });

        // Then, add it back as a bigint
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_team_id')->nullable()->after('remember_token');
        });
    }
};
