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
            // Add term weights (nullable if not using term system)
            $table
                ->unsignedTinyInteger('college_prelim_weight')
                ->nullable()
                ->after('college_grading_scale');
            $table
                ->unsignedTinyInteger('college_midterm_weight')
                ->nullable()
                ->after('college_prelim_weight');
            $table
                ->unsignedTinyInteger('college_final_weight')
                ->nullable()
                ->after('college_midterm_weight');

            // Add a specific scale for term calculation if needed, or assume the main college_grading_scale applies
            // Let's assume college_grading_scale applies to term grades AND final grade for now.
            // We modify college_grading_scale options later in the model/controller.
        });

        // Update existing teams - Set a default if applicable, or leave null
        // Example: If most college teams will use 5-point GWA initially
        // Team::where('grading_system_type', Team::GRADING_SYSTEM_COLLEGE)
        //    ->whereNull('college_grading_scale')
        //    ->update(['college_grading_scale' => Team::COLLEGE_SCALE_GWA_5_POINT]); // Add new constants later
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'college_prelim_weight',
                'college_midterm_weight',
                'college_final_weight',
            ]);
        });
    }
};
