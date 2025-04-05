<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to drop and recreate foreign keys differently
        Schema::table('activity_submissions', function (Blueprint $table) {
            // Check for existing foreign key using PostgreSQL information schema
            $foreignKeyExists = DB::select("
                SELECT 1
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                  ON tc.constraint_name = kcu.constraint_name
                WHERE tc.table_name = 'activity_submissions'
                  AND tc.constraint_type = 'FOREIGN KEY'
                  AND kcu.column_name = 'student_id'
            ");

            if (! empty($foreignKeyExists)) {
                $table->dropForeign(['student_id']);
            }

            // Add the correct foreign key constraint
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

        });

        // Log the change for debugging
        \Illuminate\Support\Facades\Log::info('Fixed foreign key constraint for student_id in activity_submissions table');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            // Check for existing foreign key using PostgreSQL information schema
            $foreignKeyExists = DB::select("
                SELECT 1
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                  ON tc.constraint_name = kcu.constraint_name
                WHERE tc.table_name = 'activity_submissions'
                  AND tc.constraint_type = 'FOREIGN KEY'
                  AND kcu.column_name = 'student_id'
            ");

            if (! empty($foreignKeyExists)) {
                $table->dropForeign(['student_id']);
            }
        });
    }
};
