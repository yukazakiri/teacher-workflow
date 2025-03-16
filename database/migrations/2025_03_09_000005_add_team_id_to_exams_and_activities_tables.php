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
        // Skip adding team_id to exams as it already exists
        
        // Only add team_id to activities if it doesn't exist
        if (!Schema::hasColumn('activities', 'team_id')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->foreignUuid('team_id')->nullable()->after('teacher_id')->constrained()->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop team_id from activities if it exists
        if (Schema::hasColumn('activities', 'team_id')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            });
        }
    }
};
