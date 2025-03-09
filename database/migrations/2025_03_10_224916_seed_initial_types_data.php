<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed question types
        $questionTypes = [
            ['id' => Str::uuid()->toString(), 'name' => 'Multiple Choice', 'description' => 'Questions with multiple options where one or more can be correct'],
            ['id' => Str::uuid()->toString(), 'name' => 'True/False', 'description' => 'Questions with binary true or false answers'],
            ['id' => Str::uuid()->toString(), 'name' => 'Short Answer', 'description' => 'Questions requiring a brief text response'],
            ['id' => Str::uuid()->toString(), 'name' => 'Essay', 'description' => 'Questions requiring an extended written response'],
            ['id' => Str::uuid()->toString(), 'name' => 'Matching', 'description' => 'Questions requiring matching items from two columns'],
            ['id' => Str::uuid()->toString(), 'name' => 'Fill in the Blank', 'description' => 'Questions with missing words to be filled in'],
        ];

        foreach ($questionTypes as $type) {
            DB::table('question_types')->insert([
                'id' => $type['id'],
                'name' => $type['name'],
                'description' => $type['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed activity types
        $activityTypes = [
            ['id' => Str::uuid()->toString(), 'name' => 'Quiz', 'description' => 'Short assessment to gauge understanding'],
            ['id' => Str::uuid()->toString(), 'name' => 'Assignment', 'description' => 'Task to be completed by students'],
            ['id' => Str::uuid()->toString(), 'name' => 'Reporting', 'description' => 'Activity requiring students to report on findings'],
            ['id' => Str::uuid()->toString(), 'name' => 'Presentation', 'description' => 'Activity requiring students to present to the class'],
            ['id' => Str::uuid()->toString(), 'name' => 'Discussion', 'description' => 'Guided discussion on a topic'],
            ['id' => Str::uuid()->toString(), 'name' => 'Project', 'description' => 'Extended activity with multiple components'],
            ['id' => Str::uuid()->toString(), 'name' => 'Lab', 'description' => 'Hands-on experimental activity'],
        ];

        foreach ($activityTypes as $type) {
            DB::table('activity_types')->insert([
                'id' => $type['id'],
                'name' => $type['name'],
                'description' => $type['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('question_types')->truncate();
        DB::table('activity_types')->truncate();
    }
};
