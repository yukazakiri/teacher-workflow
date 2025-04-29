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
            // Add columns for College Term Component Weights (WW/PT)
            // Nullable as they only apply to a specific college grading scale subtype
            $table->unsignedTinyInteger('college_term_ww_weight')->nullable()->after('college_final_weight');
            $table->unsignedTinyInteger('college_term_pt_weight')->nullable()->after('college_term_ww_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['college_term_ww_weight', 'college_term_pt_weight']);
        });
    }
};
