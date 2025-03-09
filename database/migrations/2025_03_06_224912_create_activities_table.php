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
        Schema::create('activity_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
                // $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->uuid('activity_type_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('format', ['quiz', 'assignment', 'reporting', 'presentation', 'discussion', 'project', 'other'])->default('assignment');
            $table->string('custom_format')->nullable(); // For 'other' format
            $table->enum('category', ['written', 'performance'])->default('written');
            $table->enum('mode', ['group', 'individual', 'take_home'])->default('individual');
            $table->integer('total_points')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('deadline')->nullable(); // For take-home activities
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('activity_types');
    }
};
