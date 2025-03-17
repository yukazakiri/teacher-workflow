<?php

namespace App\Http\Controllers;

use App\Models\QuestionType;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionTypesController extends Controller
{
    /**
     * Seed the question types and update existing questions.
     */
    public function seedAndUpdate()
    {
        // Seed question types if they don't exist
        $types = [
            ['name' => 'Multiple Choice', 'slug' => 'multiple_choice'],
            ['name' => 'True/False', 'slug' => 'true_false'],
            ['name' => 'Short Answer', 'slug' => 'short_answer'],
            ['name' => 'Essay', 'slug' => 'essay'],
            ['name' => 'Matching', 'slug' => 'matching'],
            ['name' => 'Fill in the Blank', 'slug' => 'fill_in_blank'],
        ];

        $typeMap = [];
        foreach ($types as $type) {
            $questionType = QuestionType::firstOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'slug' => $type['slug'],
                ]
            );
            $typeMap[$type['slug']] = $questionType->id;
        }

        // Update existing questions with correct question_type_id
        $questions = Question::whereNull('question_type_id')->get();
        foreach ($questions as $question) {
            if (isset($typeMap[$question->type])) {
                $question->question_type_id = $typeMap[$question->type];
                $question->save();
            }
        }

        return response()->json(['message' => 'Question types seeded and questions updated successfully']);
    }
}
