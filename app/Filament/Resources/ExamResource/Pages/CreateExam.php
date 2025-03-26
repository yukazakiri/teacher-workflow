<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use App\Models\Question;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    public function mount(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        
        if (!$team || !$team->userIsOwner($user)) {
            Notification::make()
                ->title('Access Denied')
                ->body('Only team owners can create exams.')
                ->danger()
                ->send();
                
            redirect()->route('filament.app.pages.dashboard', ['tenant' => $team->id ?? 1])->send();
            exit;
        }
        
        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Begin a database transaction
        return DB::transaction(function () use ($data) {
            // Remove question_sections from data as it's not a direct attribute of Exam
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

            // Create the exam record
            $exam = static::getModel()::create($data);

            // Process and create questions
            $this->createQuestionsForExam($exam, $question_sections);

            return $exam;
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
                        $question->correct_answer = [$questionData['correct_answer']];
                        $question->explanation = $questionData['explanation'] ?? null;
                        break;

                    case 'true_false':
                        $question->correct_answer = [$questionData['correct_answer']];
                        $question->explanation = $questionData['explanation'] ?? null;
                        break;

                    case 'short_answer':
                        $question->correct_answer = [$questionData['correct_answer']];
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
