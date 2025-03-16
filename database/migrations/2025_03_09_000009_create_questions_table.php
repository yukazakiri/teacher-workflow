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
        Schema::create('question_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->uuid('question_type_id');
            $table->uuid('exam_id');
            $table->uuid('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->text('content');
            $table->text('answer_key')->nullable();
            $table->integer('points')->default(1);
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('question_type_id')->references('id')->on('question_types')->onDelete('cascade');
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('question_id');
            $table->integer('order')->default(0);
            $table->integer('points')->default(1);
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_types');
    }
};
