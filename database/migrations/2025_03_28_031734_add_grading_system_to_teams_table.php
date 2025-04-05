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
        Schema::table('teams', function (Blueprint $table) {
            // Enum-like string for the active grading system
            $table
                ->string('grading_system_type')
                ->nullable()
                ->after('personal_team')
                ->comment('e.g., shs, college');
            // For college: specify the scale used
            $table
                ->string('college_grading_scale')
                ->nullable()
                ->after('grading_system_type')
                ->comment('e.g., 5_point, 4_point, percentage');
            // Add default weights for SHS (can be overridden in Gradesheet page)
            $table
                ->unsignedTinyInteger('shs_ww_weight')
                ->nullable()
                ->after('college_grading_scale');
            $table
                ->unsignedTinyInteger('shs_pt_weight')
                ->nullable()
                ->after('shs_ww_weight');
            $table
                ->unsignedTinyInteger('shs_qa_weight')
                ->nullable()
                ->after('shs_pt_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'grading_system_type',
                'college_grading_scale',
                'shs_ww_weight',
                'shs_pt_weight',
                'shs_qa_weight',
            ]);
        });
    }
};
