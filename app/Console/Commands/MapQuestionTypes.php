<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\QuestionType;

class MapQuestionTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:map-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Map existing questions to their question types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mapping question types...');

        $typeMap = QuestionType::pluck('id', 'slug')->toArray();

        $questions = Question::whereNull('question_type_id')->get();
        $count = 0;

        foreach ($questions as $question) {
            if (isset($typeMap[$question->type])) {
                $question->question_type_id = $typeMap[$question->type];
                $question->save();
                $count++;
            } else {
                $this->warn("No type mapping found for question {$question->id} with type '{$question->type}'");
            }
        }

        $this->info("Successfully mapped {$count} questions to their types.");

        return 0;
    }
}
