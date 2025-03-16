<?php

declare(strict_types=1);

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
        Schema::table('questions', function (Blueprint $table) {
            // Add columns for dynamic question types
            $table->string('type')->after('exam_id'); // multiple_choice, true_false, etc.
            $table->json('choices')->nullable()->after('content'); // For multiple choice questions
            $table->json('correct_answer')->nullable()->after('choices'); // Can be string or array depending on type
            $table->text('explanation')->nullable()->after('correct_answer'); // Explanation for the correct answer
            $table->text('rubric')->nullable()->after('explanation'); // For essay questions
            $table->integer('word_limit')->nullable()->after('rubric'); // For essay questions
            $table->json('matching_pairs')->nullable()->after('word_limit'); // For matching questions
            $table->json('answers')->nullable()->after('matching_pairs'); // For fill in the blank questions
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Remove columns added in up()
            $table->dropColumn([
                'type',
                'choices',
                'correct_answer',
                'explanation',
                'rubric',
                'word_limit',
                'matching_pairs',
                'answers',
            ]);
        });
    }
};
