<?php

namespace App\Filament\Admin\Resources\ExamResource\Pages;

use App\Filament\Admin\Resources\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If creating questions from the form
        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $key => $question) {
                // Get question type ID from the type string
                if (isset($question['type'])) {
                    $questionType = \App\Models\QuestionType::where('slug', $question['type'])->first();
                    if ($questionType) {
                        $data['questions'][$key]['question_type_id'] = $questionType->id;
                    }
                }
            }
        }

        return $data;
    }
}
