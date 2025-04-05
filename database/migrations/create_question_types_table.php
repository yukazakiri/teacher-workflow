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

        // Add question_type_id to questions table if it doesn't exist
        if (! Schema::hasColumn('questions', 'question_type_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->uuid('question_type_id')->nullable();
                $table->foreign('question_type_id')->references('id')->on('question_types');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the foreign key first if it exists
        if (Schema::hasColumn('questions', 'question_type_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['question_type_id']);
                $table->dropColumn('question_type_id');
            });
        }

        Schema::dropIfExists('question_types');
    }
};
