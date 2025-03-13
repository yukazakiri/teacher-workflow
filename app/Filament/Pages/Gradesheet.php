<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\Column;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn as TablesTextColumn;
use Filament\Tables\Actions\Action as TablesAction;
use Illuminate\Support\Facades\Log;

class Gradesheet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Gradesheet';
    protected static ?string $navigationGroup = 'Classroom Tools';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.gradesheet';

    public ?array $data = [];
    public ?int $teamId = null;
    public ?int $writtenWeight = 40;
    public ?int $performanceWeight = 60;
    public bool $showFinalGrades = true;

    // Store activity scores to avoid SQL errors when updating
    public array $activityScores = [];

    public function mount(): void
    {
        $this->teamId = Auth::user()->currentTeam->id;
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
                                        $writtenWeight = (int) $get('writtenWeight');
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
                                        $performanceWeight = (int) $get('performanceWeight');
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

    public function table(Table $table): Table
    {
        $team = Team::find($this->teamId);

        if (!$team) {
            return $table->columns([]);
        }

        // Get all activities for the team, regardless of status
        $activities = Activity::where('team_id', $this->teamId)
            ->orderBy('category')
            ->orderBy('created_at')
            ->get();

        $columns = [
            TextColumn::make('name')
                ->label('Student Name')
                ->searchable()
                ->sortable()
                ->weight('bold'),
        ];

        // Add a column for each activity
        foreach ($activities as $activity) {
            $columns[] = TablesTextColumn::make("activity_{$activity->id}")
                ->label(function () use ($activity): HtmlString {
                    $categoryBadge = $activity->isWrittenActivity()
                        ? '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-800 mr-2">W</span>'
                        : '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-800 mr-2">P</span>';

                    $label = $categoryBadge . e($activity->title) .
                        '<br><span class="text-xs text-gray-500">(' . $activity->total_points . ' pts)</span>';

                    return new HtmlString($label);
                })
                ->alignCenter()
                ->getStateUsing(function ($record) use ($activity) {
                    return $this->activityScores[$record->id][$activity->id] ?? null;
                })
                ->numeric()
                ->action(function ($record, $state) use ($activity) {
                    return TablesAction::make('updateScore')
                        ->form([
                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($activity->total_points)
                                ->step(0.01)
                                ->default($state)
                                ->required(),
                        ])
                        ->action(function (array $data) use ($record, $activity) {
                            $this->updateScore($record->id, $activity->id, $data['score']);

                            Notification::make()
                                ->title('Score updated')
                                ->success()
                                ->send();
                        });
                });
        }

        // Add final grade columns if enabled
        if ($this->showFinalGrades) {
            $columns[] = TextColumn::make('final_grade')
                ->label(new HtmlString('Final<br>Grade'))
                ->alignCenter()
                ->weight('bold')
                ->formatStateUsing(function ($state, $record) use ($activities): HtmlString {
                    $finalGradeValue = $this->calculateFinalGradeValue($record->id, $activities);

                    if ($finalGradeValue === null) {
                        return new HtmlString('<span class="text-gray-400">N/A</span>');
                    }

                    // Apply grade coloring based on score
                    $color = match (true) {
                        $finalGradeValue >= 90 => 'text-success-600 dark:text-success-400',
                        $finalGradeValue >= 80 => 'text-primary-600 dark:text-primary-400',
                        $finalGradeValue >= 70 => 'text-warning-600 dark:text-warning-400',
                        default => 'text-danger-600 dark:text-danger-400',
                    };

                    // Format the grade with percentage
                    $formattedGrade = number_format($finalGradeValue, 2) . '%';

                    return new HtmlString(
                        "<div class=\"flex flex-col items-center gap-1\">
                            <span class=\"{$color} font-bold text-lg\">{$formattedGrade}</span>
                        </div>"
                    );
                });
        }

        return $table
            ->query(
                Student::query()
                    ->where('team_id', $this->teamId)
                    ->where('status', 'active')
            )
            ->columns($columns)
            ->actions([
                TableAction::make('edit_scores')
                    ->label('Edit Scores')
                    ->icon('heroicon-m-pencil-square')
                    ->modalWidth('lg')
                    ->slideOver()
                    ->form(function (Student $record) use ($activities): array {
                        $schema = [];

                        foreach ($activities as $activity) {
                            $schema[] = TextInput::make("scores.{$activity->id}")
                                ->label(function () use ($activity): string {
                                    $categoryPrefix = $activity->isWrittenActivity() ? '[W] ' : '[P] ';
                                    return $categoryPrefix . $activity->title . ' (' . $activity->total_points . ' pts)';
                                })
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(fn() => $activity->total_points)
                                ->default(fn() => $this->activityScores[$record->id][$activity->id] ?? null);
                        }

                        return [
                            Section::make('Activity Scores')
                                ->description(fn(Student $record) => "Edit scores for {$record->name}")
                                ->schema($schema)
                                ->columns(1),
                        ];
                    })
                    ->action(function (array $data, Student $record): void {
                        foreach ($data['scores'] as $activityId => $score) {
                            $this->updateScore($record->id, $activityId, $score);
                        }

                        $this->loadActivityScores();

                        Notification::make()
                            ->title('Scores updated successfully')
                            ->success()
                            ->send();
                    }),

                TableAction::make('view_final_grade')
                    ->label('View Final Grade')
                    ->icon('heroicon-m-calculator')
                    ->color('success')
                    ->modalHeading(fn(Student $record) => "Final Grade for {$record->name}")
                    ->modalDescription('Detailed breakdown of the student\'s final grade based on activity scores.')
                    ->modalWidth('md')
                    ->action(function () {})
                    ->modalContent(function (Student $record) use ($activities): HtmlString {
                        // Get written and performance activities
                        $writtenActivities = $activities->filter(fn($activity) => $activity->isWrittenActivity());
                        $performanceActivities = $activities->filter(fn($activity) => $activity->isPerformanceActivity());

                        // Calculate category averages
                        $writtenAverage = $this->calculateCategoryAverage($record->id, $activities, 'written');
                        $performanceAverage = $this->calculateCategoryAverage($record->id, $activities, 'performance');

                        // Calculate final grade
                        $finalGrade = $this->calculateFinalGrade($record->id, $activities);

                        // Build the HTML content
                        $html = '
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Written Activities</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Weight: ' . $this->writtenWeight . '%</p>
                                    <p class="mt-4 text-2xl font-bold text-primary-600 dark:text-primary-400">' . $writtenAverage . '</p>
                                </div>

                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Performance Activities</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Weight: ' . $this->performanceWeight . '%</p>
                                    <p class="mt-4 text-2xl font-bold text-primary-600 dark:text-primary-400">' . $performanceAverage . '</p>
                                </div>
                            </div>

                            <div class="rounded-lg bg-gray-100 p-6 dark:bg-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Final Grade</h3>
                                <p class="mt-6 text-3xl">' . $finalGrade . '</p>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-base font-medium text-gray-900 dark:text-white">Activity Breakdown</h3>
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity</th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score</th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">';

                        // Add rows for each activity
                        foreach ($activities as $activity) {
                            $score = $this->activityScores[$record->id][$activity->id] ?? null;
                            $scoreDisplay = $score !== null ? $score : '-';

                            // Format percentage with % symbol
                            $percentageDisplay = $score !== null
                                ? number_format(($score / $activity->total_points) * 100, 1) . '%'
                                : '-';

                            // Apply color based on percentage
                            $percentageColor = '';
                            if ($score !== null) {
                                $percentage = ($score / $activity->total_points) * 100;
                                $percentageColor = match(true) {
                                    $percentage >= 90 => 'text-success-600 dark:text-success-400',
                                    $percentage >= 80 => 'text-primary-600 dark:text-primary-400',
                                    $percentage >= 70 => 'text-warning-600 dark:text-warning-400',
                                    default => 'text-danger-600 dark:text-danger-400',
                                };
                            }

                            // Determine category badge
                            $categoryBadge = $activity->isWrittenActivity()
                                ? '<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">W</span>'
                                : '<span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">P</span>';

                            $html .= '
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        ' . $categoryBadge . ' ' . e($activity->title) . '
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium text-gray-900 dark:text-white">' . $scoreDisplay . '</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-500 dark:text-gray-400">' . $activity->total_points . '</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium ' . $percentageColor . '">' . $percentageDisplay . '</td>
                                </tr>';
                        }

                        $html .= '
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>';

                        return new HtmlString($html);
                    })
            ])
            ->striped()
            ->paginated(false)
            ->recordClasses(fn($record) => 'h-14');
    }

    public function updateScore($studentId, $activityId, $score): void
    {
        try {
            // Log for debugging
            Log::info('Gradesheet - Updating Score', [
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'score' => $score,
            ]);

            $submission = ActivitySubmission::firstOrNew([
                'activity_id' => $activityId,
                'student_id' => $studentId,
            ]);

            $submission->score = $score !== '' && $score !== null ? (float) $score : null;

            if (!$submission->exists) {
                $submission->status = 'graded';
                $submission->graded_by = Auth::id();
                $submission->graded_at = now();
                $submission->submitted_at = now();
            } else if ($submission->score !== null && $submission->status !== 'graded') {
                $submission->status = 'graded';
                $submission->graded_by = Auth::id();
                $submission->graded_at = now();
            }

            $submission->save();

            // Update our local cache
            $this->activityScores[$studentId][$activityId] = $submission->score;

            Log::info('Gradesheet - Score Updated Successfully', [
                'submission_id' => $submission->id,
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'score' => $score,
            ]);
        } catch (\Exception $e) {
            Log::error('Gradesheet - Error Updating Score', [
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Notify the user of the error
            Notification::make()
                ->title('Error updating score')
                ->body('There was an error updating the score. Please try again.')
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
                            ActivitySubmission::where('student_id', $student->id)
                                ->whereIn('activity_id', $activities->pluck('id'))
                                ->update(['final_grade' => $finalGrade]);
                        }
                    }

                    Notification::make()
                        ->title('Final grades calculated successfully')
                        ->success()
                        ->send();

                    $this->loadActivityScores();
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
}
