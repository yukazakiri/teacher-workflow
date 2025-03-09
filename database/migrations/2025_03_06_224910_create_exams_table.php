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
        Schema::create("exams", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("teacher_id");
            $table->foreignId("team_id")->constrained()->cascadeOnDelete();
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
