<?php

use App\Models\Activity;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint; // Import Activity model for constants
use Illuminate\Support\Facades\Schema; // Import Team model for constants

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update activities table
        Schema::table('activities', function (Blueprint $table) {
            // SHS Specific
            // $table->string('component_type')->nullable()->after('category')
            //       ->comment('SHS component: written_work, performance_task, quarterly_assessment');

            // College Specific
            // $table->string('term')->nullable()->after('component_type')
            //   ->comment('College term: prelim, midterm, final');
            // $table->decimal('credit_units', 5, 2)->nullable()->after('total_points')
            //         ->comment('College credit units for GWA calculation');

            // Add indexes for faster lookups
            // $table->index('component_type');
            // $table->index('term');
        });

        // Update teams table
        Schema::table('teams', function (Blueprint $table) {
            // Ensure grading system type column exists (might be added by Jetstream/previous migrations)
            if (! Schema::hasColumn('teams', 'grading_system_type')) {
                $table->string('grading_system_type')->nullable()->after('join_code');
            }
            // Ensure college scale column exists
            if (! Schema::hasColumn('teams', 'college_grading_scale')) {
                $table->string('college_grading_scale')->nullable()->after('grading_system_type');
            }

            // Add SHS weights (nullable integers)
            // $table->unsignedTinyInteger('shs_ww_weight')->nullable()->after('college_grading_scale');
            // $table->unsignedTinyInteger('shs_pt_weight')->nullable()->after('shs_ww_weight');
            // $table->unsignedTinyInteger('shs_qa_weight')->nullable()->after('shs_pt_weight');

            // Add College term weights (nullable integers)
            // $table->unsignedTinyInteger('college_prelim_weight')->nullable()->after('shs_qa_weight');
            // $table->unsignedTinyInteger('college_midterm_weight')->nullable()->after('college_prelim_weight');
            // $table->unsignedTinyInteger('college_final_weight')->nullable()->after('college_midterm_weight');

            // Add onboarding step if it doesn't exist
            // if (!Schema::hasColumn('teams', 'onboarding_step')) {
            //    $table->unsignedTinyInteger('onboarding_step')->default(0)->after('college_final_weight');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['component_type']);
            $table->dropIndex(['term']);
            $table->dropColumn(['component_type', 'term', 'credit_units']);
        });

        Schema::table('teams', function (Blueprint $table) {
            // Drop columns if they were added by this migration
            // Note: Be cautious dropping grading_system_type/college_grading_scale if added elsewhere
            // if (Schema::hasColumn('teams', 'grading_system_type')) { $table->dropColumn('grading_system_type'); }
            // if (Schema::hasColumn('teams', 'college_grading_scale')) { $table->dropColumn('college_grading_scale'); }
            $table->dropColumn([
                'shs_ww_weight', 'shs_pt_weight', 'shs_qa_weight',
                'college_prelim_weight', 'college_midterm_weight', 'college_final_weight',
            ]);
            // if (Schema::hasColumn('teams', 'onboarding_step')) { $table->dropColumn('onboarding_step'); }
        });
    }
};
