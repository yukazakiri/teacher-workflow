<?php

declare(strict_types=1);

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
        Schema::table('activity_submissions', function (Blueprint $table) {
            // Rename activity_group_id to group_id
            if (Schema::hasColumn('activity_submissions', 'activity_group_id')) {
                $table->renameColumn('activity_group_id', 'group_id');
            } else {
                $table->uuid('group_id')->nullable()->after('student_id');
            }

            // Add graded_by and graded_at columns
            if (! Schema::hasColumn('activity_submissions', 'graded_by')) {
                $table->uuid('graded_by')->nullable()->after('feedback');
                $table->foreign('graded_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('activity_submissions', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('graded_by');
            }

            // Update foreign key for group_id
            if (Schema::hasColumn('activity_submissions', 'group_id')) {
                $table->foreign('group_id')
                    ->references('id')
                    ->on('groups')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['graded_by']);
            $table->dropForeign(['group_id']);

            // Drop columns
            $table->dropColumn('graded_at');
            $table->dropColumn('graded_by');

            // Rename group_id back to activity_group_id
            $table->renameColumn('group_id', 'activity_group_id');
        });
    }
};
