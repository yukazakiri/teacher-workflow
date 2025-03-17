<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('question_types', 'slug')) {
            Schema::table('question_types', function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
            });

            // Update existing records with slugs
            $types = [
                'Multiple Choice' => 'multiple_choice',
                'True/False' => 'true_false',
                'Short Answer' => 'short_answer',
                'Essay' => 'essay',
                'Matching' => 'matching',
                'Fill in the Blank' => 'fill_in_blank',
            ];

            foreach ($types as $name => $slug) {
                DB::table('question_types')
                    ->where('name', $name)
                    ->update(['slug' => $slug]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('question_types', 'slug')) {
            Schema::table('question_types', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
