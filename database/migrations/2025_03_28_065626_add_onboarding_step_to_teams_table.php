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
        Schema::table("teams", function (Blueprint $table) {
            // Add step tracking. 0 = initial, 1 = shown add students, 2 = shown create activities, etc.
            $table
                ->unsignedTinyInteger("onboarding_step")
                ->default(0)
                ->after("personal_team");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("teams", function (Blueprint $table) {
            $table->dropColumn("onboarding_step");
        });
    }
};
