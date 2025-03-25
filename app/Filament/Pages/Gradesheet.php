<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Student;
use App\Models\Team;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;

class Gradesheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Gradesheet';
    protected static ?string $navigationGroup = 'Classroom Tools';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.gradesheet';

    public ?array $data = [];
    public ?string $teamId = null;
    public ?int $writtenWeight = 40;
    public ?int $performanceWeight = 60;
    public bool $showFinalGrades = true;

    // Store activity scores
    public array $activityScores = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->teamId = $user->currentTeam->id;  // Directly use the current team

        $this->form->fill();
        $this->loadActivityScores();
    }

    protected function loadActivityScores(): void
    {
        $team = Team::find($this->teamId);
        if (!$team) return;

        $students = Student::where('team_id', $this->teamId)
            ->where('status', 'active')
            ->get();

        $activities = Activity::where('team_id', $this->teamId)
            ->orderBy('category')
            ->orderBy('created_at')
            ->get();

        foreach ($students as $student) {
            foreach ($activities as $activity) {
                $submission = ActivitySubmission::where('activity_id', $activity->id)
                    ->where('student_id', $student->id)
                    ->first();

                $this->activityScores[$student->id][$activity->id] = $submission ? $submission->score : null;
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Grading Configuration')
                    ->description('Configure how grades are calculated')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('writtenWeight')
                                    ->label('Written Activities Weight (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(40)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $writtenWeight = (int)$get('writtenWeight');
                                        $set('performanceWeight', 100 - $writtenWeight);
                                    }),

                                TextInput::make('performanceWeight')
                                    ->label('Performance Activities Weight (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(60)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $performanceWeight = (int)$get('performanceWeight');
                                        $set('writtenWeight', 100 - $performanceWeight);
                                    }),

                                Toggle::make('showFinalGrades')
                                    ->label('Show Final Grades')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Save all scores in the gradesheet
     */
    public function saveAllScores(): void
    {
        try {
            $team = Team::find($this->teamId);
            if (!$team) {
                Notification::make()
                    ->title('Error')
                    ->body('Could not find team.')
                    ->danger()
                    ->send();
                return;
            }

            $updatedCount = 0;
            $students = Student::where('team_id', $this->teamId)
                ->where('status', 'active')
                ->get();
            
            $activities = Activity::where('team_id', $this->teamId)
                ->orderBy('category')
                ->orderBy('created_at')
                ->get();
            
            foreach ($students as $student) {
                foreach ($activities as $activity) {
                    if (isset($this->activityScores[$student->id][$activity->id])) {
                        $score = $this->activityScores[$student->id][$activity->id];
                        
                        if ($score === '' || $score === null) {
                            continue; // Skip empty scores
                        }
                        
                        $submission = ActivitySubmission::firstOrNew([
                            'activity_id' => $activity->id,
                            'student_id' => $student->id,
                        ]);
                        
                        // Only update if the score has changed
                        if ($submission->score != (float)$score) {
                            $submission->score = (float)$score;
                            $submission->status = 'graded';
                            $submission->graded_by = Auth::id();
                            $submission->graded_at = now();
                            $submission->save();
                            
                            $updatedCount++;
                        }
                    }
                }
            }
            
            // Calculate and save final grades
            foreach ($students as $student) {
                $finalGrade = $this->calculateFinalGradeValue($student->id, $activities);
                
                if ($finalGrade !== null) {
                    ActivitySubmission::where('student_id', $student->id)
                        ->whereIn('activity_id', $activities->pluck('id'))
                        ->update(['final_grade' => $finalGrade]);
                }
            }
            
            // Show success notification
            Notification::make()
                ->title('Grades Saved')
                ->body("Successfully updated $updatedCount scores.")
                ->success()
                ->send();
            
            // Refresh activity scores
            $this->loadActivityScores();
            
        } catch (\Exception $e) {
            Log::error("Error saving scores: " . $e->getMessage());
            Notification::make()
                ->title('Error')
                ->body('Error saving scores. Please try again.')
                ->danger()
                ->send();
        }
    }

    /**
     * Update a single score for a student's activity
     * @deprecated Use saveAllScores() instead
     */
    public function updateScore($studentId, $activityId): void
    {
        try {
            // Access the score directly from the activityScores array
            $score = $this->activityScores[$studentId][$activityId] ?? null;

            Log::info("Updating score for student $studentId, activity $activityId: $score");

            $submission = ActivitySubmission::firstOrNew([
                'activity_id' => $activityId,
                'student_id' => $studentId,
            ]);

            $submission->score = $score !== '' && $score !== null ? (float)$score : null;

            if (!$submission->exists || $submission->score !== null) {
                $submission->status = 'graded';
                $submission->graded_by = Auth::id();
                $submission->graded_at = now();
            }

            $submission->save();
            // No need to update $this->activityScores, it's already up-to-date

            // Calculate and save the final grade for this student
            $activities = Activity::where('team_id', $this->teamId)
                ->where('status', 'published')
                ->get();

            $finalGrade = $this->calculateFinalGradeValue($studentId, $activities);

            if ($finalGrade !== null) {
                // Update all submissions for this student with the final grade
                ActivitySubmission::where('student_id', $studentId)
                    ->whereIn('activity_id', $activities->pluck('id'))
                    ->update(['final_grade' => $finalGrade]);
            }

            Log::info("Score updated successfully: submission ID $submission->id");

            // Send Filament notification
            Notification::make()
                ->title('Score Updated')
                ->body("Score updated for {$submission->student->name} on {$submission->activity->title}.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("Error updating score: " . $e->getMessage());
            Notification::make()
                ->title('Error')
                ->body('Error updating score. Please try again.')
                ->danger()
                ->send();
        }
    }

    protected function calculateCategoryAverage(string $studentId, Collection $activities, string $category): string
    {
        $categoryActivities = $activities->filter(function ($activity) use ($category) {
            return $activity->category === $category;
        });

        if ($categoryActivities->isEmpty()) {
            return 'N/A';
        }

        $totalPoints = 0;
        $earnedPoints = 0;
        $submissionCount = 0;

        foreach ($categoryActivities as $activity) {
            $score = $this->activityScores[$studentId][$activity->id] ?? null;

            if ($score !== null) {
                $totalPoints += $activity->total_points;
                $earnedPoints += $score;
                $submissionCount++;
            }
        }

        if ($submissionCount === 0 || $totalPoints === 0) {
            return 'N/A';
        }

        $average = ($earnedPoints / $totalPoints) * 100;
        return number_format($average, 2) . '%';
    }

    protected function calculateFinalGrade(string $studentId, Collection $activities): string
    {
        $finalGrade = $this->calculateFinalGradeValue($studentId, $activities);

        if ($finalGrade === null) {
            return 'N/A';
        }

        // Apply grade coloring based on score
        $color = match (true) {
            $finalGrade >= 90 => 'text-success-600',
            $finalGrade >= 80 => 'text-primary-600',
            $finalGrade >= 70 => 'text-warning-600',
            default => 'text-danger-600',
        };

        return "<span class=\"{$color} font-bold\">" . number_format($finalGrade, 2) . '%</span>';
    }

    protected function calculateFinalGradeValue(string $studentId, Collection $activities): ?float
    {
        if ($activities->isEmpty()) {
            return null;
        }

        $writtenActivities = $activities->filter(fn($activity) => $activity->category === 'written');
        $performanceActivities = $activities->filter(fn($activity) => $activity->category === 'performance');

        $writtenTotal = 0;
        $writtenEarned = 0;
        $performanceTotal = 0;
        $performanceEarned = 0;

        foreach ($writtenActivities as $activity) {
            $score = $this->activityScores[$studentId][$activity->id] ?? null;
            if ($score !== null) {
                $writtenTotal += $activity->total_points;
                $writtenEarned += $score;
            }
        }

        foreach ($performanceActivities as $activity) {
            $score = $this->activityScores[$studentId][$activity->id] ?? null;
            if ($score !== null) {
                $performanceTotal += $activity->total_points;
                $performanceEarned += $score;
            }
        }

        if ($writtenTotal === 0 && $performanceTotal === 0) {
            return null;
        }

        $writtenPercentage = $writtenTotal > 0 ? ($writtenEarned / $writtenTotal) * 100 : 0;
        $performancePercentage = $performanceTotal > 0 ? ($performanceEarned / $performanceTotal) * 100 : 0;

        $writtenWeight = $this->writtenWeight / 100;
        $performanceWeight = $this->performanceWeight / 100;

        $finalGrade = ($writtenPercentage * $writtenWeight) + ($performancePercentage * $performanceWeight);
        return round($finalGrade, 2);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('calculateFinalGrades')
                ->label('Calculate Final Grades')
                ->icon('heroicon-o-calculator')
                ->size(ActionSize::Large)
                ->color('primary')
                ->action(function () {
                    $team = Team::find($this->teamId);
                    if (!$team) return;

                    $students = Student::where('team_id', $this->teamId)
                        ->where('status', 'active')
                        ->get();

                    $activities = Activity::where('team_id', $this->teamId)
                        ->where('status', 'published')
                        ->get();

                    foreach ($students as $student) {
                        $finalGrade = $this->calculateFinalGradeValue($student->id, $activities);
                        if ($finalGrade !== null) {
                            // Update *all* submissions for this student with the final grade
                            ActivitySubmission::where('student_id', $student->id)
                                ->whereIn('activity_id', $activities->pluck('id'))
                                ->update(['final_grade' => $finalGrade]);
                        }
                    }

                    // Use Filament's notification
                    Notification::make()
                        ->title('Final Grades Calculated')
                        ->body('Final grades have been calculated and updated.')
                        ->success()
                        ->send();

                    $this->loadActivityScores(); // Refresh
                }),

            Action::make('export')
                ->label('Export Grades')
                ->icon('heroicon-o-arrow-down-tray')
                ->size(ActionSize::Large)
                ->color('success')
                ->action(function () {
                    return $this->exportGrades();
                }),
        ];
    }

    protected function exportGrades()
    {
        $team = Team::find($this->teamId);

        if (!$team) {
            return;
        }

        $students = Student::where('team_id', $this->teamId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $activities = Activity::where('team_id', $this->teamId)
            ->where('status', 'published')
            ->orderBy('category')
            ->orderBy('created_at')
            ->get();

        // Create CSV headers
        $headers = ['Student Name'];

        foreach ($activities as $activity) {
            $categoryPrefix = $activity->isWrittenActivity() ? '[W] ' : '[P] ';
            $headers[] = $categoryPrefix . $activity->title . ' (' . $activity->total_points . ' pts)';
        }

        if ($this->showFinalGrades) {
            $headers[] = 'Final Grade';
        }

        // Create CSV data
        $data = [];
        $data[] = $headers;

        foreach ($students as $student) {
            $row = [$student->name];

            foreach ($activities as $activity) {
                $score = $this->activityScores[$student->id][$activity->id] ?? null;
                $row[] = $score !== null ? $score : '';
            }

            if ($this->showFinalGrades) {
                $finalGrade = $this->calculateFinalGradeValue($student->id, $activities);
                $row[] = $finalGrade !== null ? number_format($finalGrade, 2) : '';
            }

            $data[] = $row;
        }

        // Generate CSV content
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        $filename = $team->name . ' - Gradesheet - ' . now()->format('Y-m-d') . '.csv';

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function openFinalGradeModal(string $studentId): void
    {
        $this->dispatch('openFinalGradeModal', $studentId);
    }

    /**
     * Get all scores and activities for a specific student
     */
    public function getStudentScores(string $studentId): array
    {
         $student = Student::findOrFail($studentId);
        
        // Get all activities for the team
        $activities = Activity::where('team_id', $this->teamId)
            ->orderBy('category')
            ->orderBy('created_at')
            ->get();
            
        // Get the scores
        $scores = [];
        foreach ($activities as $activity) {
            $scores[$activity->id] = $this->activityScores[$studentId][$activity->id] ?? null;
        }
        
        return [
            'student' => $student,
            'activities' => $activities,
            'scores' => $scores,
        ];
    }
}
