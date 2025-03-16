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
        Schema::table('activities', function (Blueprint $table) {
            // Add new team_id column with correct type and foreign key constraint
            $table->foreignUuid('team_id')->nullable()->after('activity_type_id')->constrained('teams')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['team_id']);

            // Drop the new team_id column
            $table->dropColumn('team_id');

            // Add back the original team_id column
            $table->uuid('team_id')->nullable()->after('activity_type_id');

            // Add back the original foreign key constraint
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }
};
