<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("activities", function (Blueprint $table) {
            // For SHS: Component type
            $table
                ->string("component_type")
                ->nullable()
                ->after("category")
                ->comment(
                    "e.g., written_work, performance_task, quarterly_assessment"
                );
            // For College: Credit units
            $table
                ->decimal("credit_units", 4, 2)
                ->nullable()
                ->after("total_points"); // e.g., 3.00 units
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("activities", function (Blueprint $table) {
            $table->dropColumn(["component_type", "credit_units"]);
        });
    }
};
