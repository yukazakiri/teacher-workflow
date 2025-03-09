<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use App\Models\Question;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the exam with its questions
        $exam = $this->record;
        $questions = $exam->questions()->orderBy('created_at')->get();

        // Initialize empty question sections
        $data['question_sections'] = [];

        // Group questions by type
        $questionsByType = [
            'multiple_choice' => [],
            'true_false' => [],
            'short_answer' => [],
            'essay' => [],
            'matching' => [],
            'fill_in_blank' => [],
        ];

        // Process each question and add it to the appropriate type group
        foreach ($questions as $question) {
            $type = $question->type;

            // Skip if this type isn't in our predefined types
            if (!isset($questionsByType[$type])) {
                continue;
            }

            // Create question data based on its type
            $questionData = [
                'content' => $question->content,
                'points' => $question->points,
            ];

            // Add type-specific fields
            switch ($type) {
                case 'multiple_choice':
                    $questionData['choices'] = $question->choices;
                    $questionData['correct_answer'] = $question->correct_answer;
                    $questionData['explanation'] = $question->explanation;
                    break;

                case 'true_false':
                    $questionData['correct_answer'] = $question->correct_answer;
                    $questionData['explanation'] = $question->explanation;
                    break;

                case 'short_answer':
                    $questionData['correct_answer'] = $question->correct_answer;
                    $questionData['explanation'] = $question->explanation;
                    break;

                case 'essay':
                    $questionData['rubric'] = $question->rubric;
                    $questionData['word_limit'] = $question->word_limit;
                    break;

                case 'matching':
                    $questionData['matching_pairs'] = $question->matching_pairs;
                    break;

                case 'fill_in_blank':
                    $questionData['answers'] = $question->answers;
                    break;
            }

            // Add the processed question to its type group
            $questionsByType[$type][] = $questionData;
        }

        // Create sections for each question type that has questions
        $sectionIndex = 0;
        foreach ($questionsByType as $type => $typeQuestions) {
            if (!empty($typeQuestions)) {
                // Add a section for this question type
                $data['question_sections'][] = [
                    'type' => "{$type}_section",
                    'data' => [
                        'questions' => $typeQuestions
                    ]
                ];
                $sectionIndex++;
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Begin a database transaction
        return DB::transaction(function () use ($record, $data) {
            // Extract question sections from form data
            $question_sections = $data['question_sections'] ?? [];
            unset($data['question_sections']);

            // Calculate total points from all questions
            $totalPoints = 0;
            foreach ($question_sections as $section) {
                foreach ($section['data']['questions'] ?? [] as $question) {
                    $totalPoints += $question['points'] ?? 0;
                }
            }

            // Add total points to exam data
            $data['total_points'] = $totalPoints;

            // Update the exam record
            $record->update($data);

            // Delete existing questions
            $record->questions()->delete();

            // Create new questions
            $this->createQuestionsForExam($record, $question_sections);

            return $record;
        });
    }

    protected function createQuestionsForExam(Model $exam, array $question_sections): void
    {
        $order = 1;

        foreach ($question_sections as $section) {
            $sectionType = $section['type']; // e.g., 'multiple_choice_section'
            $questionType = str_replace('_section', '', $sectionType); // e.g., 'multiple_choice'

            // Process each question in the section
            foreach ($section['data']['questions'] as $questionData) {
                // Create question record
                $question = new Question();
                $question->fill([
                    'teacher_id' => Auth::id(),
                    'exam_id' => $exam->id,
                    'team_id' => Auth::user()->currentTeam->id,
                    'type' => $questionType,
                    'content' => $questionData['content'],
                    'points' => $questionData['points'],
                ]);

                // Set specific fields based on question type
                switch ($questionType) {
                    case 'multiple_choice':
                                            $question->choices = $questionData['choices'];
                                            $question->correct_answer = $questionData['correct_answer'];
                                            $question->explanation = $questionData['explanation'] ?? null;
                                            break;

                                        case 'true_false':
                                            $question->correct_answer = $questionData['correct_answer'];
                                            $question->explanation = $questionData['explanation'] ?? null;
                                            break;

                                        case 'short_answer':
                                            $question->correct_answer = $questionData['correct_answer'];
                                            $question->explanation = $questionData['explanation'] ?? null;
                                            break;

                                        case 'essay':
                                            $question->rubric = $questionData['rubric'];
                                            $question->word_limit = $questionData['word_limit'] ?? null;
                                            break;

                                        case 'matching':
                                            $question->matching_pairs = $questionData['matching_pairs'];
                                            break;

                                        case 'fill_in_blank':
                                            $question->answers = $questionData['answers'];
                                            break;
                                    }

                                    $question->save();

                                    // Create exam_question pivot entry
                                    if (method_exists($exam, 'examQuestions')) {
                                        $exam->examQuestions()->create([
                                            'question_id' => $question->id,
                                            'order' => $order,
                                            'points' => $questionData['points'],
                                        ]);
                                    }

                                    $order++;
                                }
                            }

                            // Update the exam's total points
                            $exam->updateTotalPoints();
                        }
                    }
