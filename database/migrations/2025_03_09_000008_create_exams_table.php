<?php

// Renamed from 2025_03_06_224910_create_exams_table.php to 2025_03_10_000000_create_exams_table.php
// to ensure proper migration order as teams table must be created first

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("exams", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("teacher_id");
            $table->uuid("team_id");
            $table->foreign("team_id")->references("id")->on("teams")->onDelete("cascade");
            $table->string("title");
            $table->text("description")->nullable();
            $table->integer("total_points")->default(0);
            $table
                ->enum("status", ["draft", "published", "archived"])
                ->default("draft");
            $table->timestamps();

            $table
                ->foreign("teacher_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("exams");
    }
};
