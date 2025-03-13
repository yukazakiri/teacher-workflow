<?php

declare(strict_types=1);

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
        // For SQLite, we need to drop and recreate foreign keys differently
        Schema::table('activity_progress', function (Blueprint $table) {
            // Disable foreign key constraints temporarily
            DB::statement('PRAGMA foreign_keys = OFF');

            // Add the correct foreign key constraint
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            // Re-enable foreign key constraints
            DB::statement('PRAGMA foreign_keys = ON');
        });

        // Log the change for debugging
        \Illuminate\Support\Facades\Log::info('Fixed foreign key constraint for student_id in activity_progress table');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_progress', function (Blueprint $table) {
            // Disable foreign key constraints temporarily
            DB::statement('PRAGMA foreign_keys = OFF');

            // SQLite doesn't support dropForeign directly
            // We would need to recreate the table to truly revert this change

            // Re-enable foreign key constraints
            DB::statement('PRAGMA foreign_keys = ON');
        });
    }
};
