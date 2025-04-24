<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Student;
use App\Models\Team;
use App\Services\GradingService;
use Filament\Actions\Action; // Added
use Filament\Forms\Components\Fieldset; // Added
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\HtmlString; // Added for transaction
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
class Gradesheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Gradesheet';

    protected static ?string $navigationGroup = 'Classroom Tools';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.gradesheet';

    public ?array $data = []; // Holds form data

    public ?Team $team = null; // Hold the team model

    public ?string $teamId = null;

    public ?string $gradingSystemType = null;

    public ?string $collegeGradingScale = null;

    // SHS Weights
    public ?int $shsWrittenWorkWeight = null;

    public ?int $shsPerformanceTaskWeight = null;

    public ?int $shsQuarterlyAssessmentWeight = null;

    // Add Term Weights properties
    public ?int $collegePrelimWeight = null;

    public ?int $collegeMidtermWeight = null;

    public ?int $collegeFinalWeight = null;

    public array $studentTermGrades = [];

    // Display Toggle
    public bool $showFinalGrades = true; // Rename this? Maybe showOverallGrade?

    // Store activity scores (student_id => activity_id => score)
    public array $activityScores = [];

    // Store calculated overall grades (student_id => grade_value) - calculated dynamically
    public array $studentOverallGrades = [];

    // Store fetched activities and students to avoid repeated queries in view
    public Collection $students;

    public Collection $activities;

    protected GradingService $gradingService; // Inject service

    /**
     * Check if the current user can access this page
     * Only team owners should be able to access the gradesheet
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        return $team && $team->userIsOwner($user);
    }

    public function boot(GradingService $gradingService): void
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Determine if this page's navigation item should be displayed.
     * Only show it for team owners.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Get the navigation items for this page.
     * Only team owners should see these navigation items.
     */
    public static function getNavigationItems(): array
    {
        if (! static::canAccess()) {
            return [];
        }

        return parent::getNavigationItems();
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->currentTeam) {
            // Redirect or handle appropriately if no current team
            Notification::make()
                ->title('Error')
                ->body('No active team selected.')
                ->danger()
                ->send();
            redirect()->route('filament.app.pages.dashboard')->send(); // Adjust route if needed
            exit();
        }

        $this->team = $user->currentTeam;
        $this->teamId = $this->team->id;
        $this->gradingSystemType = $this->team->grading_system_type;
        $this->collegeGradingScale = $this->team->college_grading_scale;

        if (! $this->team->userIsOwner($user)) {
            Notification::make()
                ->title('Access Denied')
                ->body('Only team owners can access the gradesheet.')
                ->danger()
                ->send();
            // Use Filament's tenant routing helper if applicable, otherwise standard route
            redirect()
                ->route('filament.app.pages.dashboard', [
                    'tenant' => $this->teamId,
                ])
                ->send();
            exit();
        }
        $this->collegePrelimWeight =
            $this->team->college_prelim_weight ??
            ($this->team->usesCollegeTermGrading() ? 30 : null);
        $this->collegeMidtermWeight =
            $this->team->college_midterm_weight ??
            ($this->team->usesCollegeTermGrading() ? 30 : null);
        $this->collegeFinalWeight =
            $this->team->college_final_weight ??
            ($this->team->usesCollegeTermGrading() ? 40 : null);
        // Set initial weights from team or defaults
        $this->shsWrittenWorkWeight =
            $this->team->shs_ww_weight ??
            ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS ? 30 : null);
        $this->shsPerformanceTaskWeight =
            $this->team->shs_pt_weight ??
            ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS ? 50 : null);
        $this->shsQuarterlyAssessmentWeight =
            $this->team->shs_qa_weight ??
            ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS ? 20 : null);

        $this->loadInitialData();
        $this->calculateAllStudentOverallGrades(); // Calculate initial grades

        // Fill form with current settings
        $this->form->fill([
            'gradingSystemType' => $this->gradingSystemType,
            'collegeGradingScale' => $this->collegeGradingScale, // This now includes term/gwa type
            // SHS Weights
            'shsWrittenWorkWeight' => $this->shsWrittenWorkWeight,
            'shsPerformanceTaskWeight' => $this->shsPerformanceTaskWeight,
            'shsQuarterlyAssessmentWeight' => $this->shsQuarterlyAssessmentWeight,
            // College Term Weights
            'collegePrelimWeight' => $this->collegePrelimWeight,
            'collegeMidtermWeight' => $this->collegeMidtermWeight,
            'collegeFinalWeight' => $this->collegeFinalWeight,
            // Display Toggle
            'showFinalGrades' => $this->showFinalGrades,
        ]);
    }

    protected function loadActivityScores(): void
    {
        if (! $this->students || ! $this->activities) {
            return;
        } // Ensure data is loaded

        $submissions = ActivitySubmission::whereIn(
            'student_id',
            $this->students->pluck('id')
        )
            ->whereIn('activity_id', $this->activities->pluck('id'))
            ->get()
            ->keyBy(fn ($item) => $item->student_id.'-'.$item->activity_id);

        $newScores = [];
        foreach ($this->students as $student) {
            foreach ($this->activities as $activity) {
                $key = $student->id.'-'.$activity->id;
                $newScores[$student->id][$activity->id] = $submissions->has(
                    $key
                )
                    ? $submissions[$key]->score
                    : null;
            }
        }
        $this->activityScores = $newScores;
        // Recalculate overall grades whenever scores are loaded/reloaded
        $this->calculateAllStudentOverallGrades();
    }

    protected function loadInitialData(): void
    {
        $this->students = Student::where('team_id', $this->teamId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Start building the activities query
        $activitiesQuery = Activity::where('team_id', $this->teamId)->where(
            'status',
            'published'
        ); // Only include published activities

        // Apply sorting based on the TEAM's grading system type
        if ($this->team->usesCollegeGrading()) {
            // If College, order primarily by Term (Prelim, Midterm, Final)
            $activitiesQuery->orderByRaw(
                '
                    CASE term
                        WHEN ? THEN 1 -- Prelim first
                        WHEN ? THEN 2 -- Midterm second
                        WHEN ? THEN 3 -- Final third
                        ELSE 4       -- Activities without a term come after specific terms
                    END ASC
                ',
                [
                    Activity::TERM_PRELIM,
                    Activity::TERM_MIDTERM,
                    Activity::TERM_FINAL,
                ]
            );
        } elseif ($this->team->usesShsGrading()) {
            // If SHS, order primarily by Component Type (WW, PT, QA)
            $activitiesQuery->orderByRaw(
                '
                    CASE component_type
                        WHEN ? THEN 1 -- Written Work first
                        WHEN ? THEN 2 -- Performance Task second
                        WHEN ? THEN 3 -- Quarterly Assessment third
                        ELSE 4       -- Activities without a component come after specific components
                    END ASC
                ',
                [
                    Activity::COMPONENT_WRITTEN_WORK,
                    Activity::COMPONENT_PERFORMANCE_TASK,
                    Activity::COMPONENT_QUARTERLY_ASSESSMENT,
                ]
            );
        }
        // Always apply a secondary sort by creation date for consistent ordering within groups
        $activitiesQuery->orderBy('created_at', 'asc');

        // Execute the query
        $this->activities = $activitiesQuery->get();

        // Load scores after activities are fetched
        $this->loadActivityScores();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Grading System Configuration')
                    ->description(
                        'Select and configure the grading system for this team.'
                    )
                    ->schema([
                        Select::make('gradingSystemType')
                            ->label('Grading System')
                            ->options([
                                Team::GRADING_SYSTEM_SHS => 'K-12 SHS (Written, Performance, Quarterly)',
                                Team::GRADING_SYSTEM_COLLEGE => 'College/University (GWA/GPA)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (
                                Set $set,
                                Get $get,
                                ?string $state
                            ) {
                                // Reset specifics when system type changes
                                $set('collegeGradingScale', null); // Reset college scale/type
                                $set('collegePrelimWeight', null);
                                $set('collegeMidtermWeight', null);
                                $set('collegeFinalWeight', null);
                                // Reset SHS weights
                                $isShs = $state === Team::GRADING_SYSTEM_SHS;
                                $set(
                                    'shsWrittenWorkWeight',
                                    $isShs ? 30 : null
                                );
                                $set(
                                    'shsPerformanceTaskWeight',
                                    $isShs ? 50 : null
                                );
                                $set(
                                    'shsQuarterlyAssessmentWeight',
                                    $isShs ? 20 : null
                                );
                                $this->updateTeamGradingSystem($state, null); // Persist change
                            }),

                        // --- College Specific ---
                        Select::make('collegeGradingScale')
                            ->label('College Calculation & Scale')
                            ->options([
                                // Group options for clarity
                                'GWA Based' => [
                                    Team::COLLEGE_SCALE_GWA_5_POINT => 'GWA - 5 Point Scale (1.00 Highest)',
                                    Team::COLLEGE_SCALE_GWA_4_POINT => 'GWA - 4 Point Scale (4.00 Highest)',
                                    Team::COLLEGE_SCALE_GWA_PERCENTAGE => 'GWA - Percentage (0-100%)',
                                ],
                                'Term Based (Prelim, Midterm, Final)' => [
                                    Team::COLLEGE_SCALE_TERM_5_POINT => 'Term Weighted - 5 Point Scale',
                                    Team::COLLEGE_SCALE_TERM_4_POINT => 'Term Weighted - 4 Point Scale',
                                    Team::COLLEGE_SCALE_TERM_PERCENTAGE => 'Term Weighted - Percentage Scale',
                                ],
                            ])
                            ->required()
                            ->live()
                            ->visible(
                                fn (Get $get) => $get('gradingSystemType') ===
                                    Team::GRADING_SYSTEM_COLLEGE
                            )
                            ->afterStateUpdated(function (
                                Set $set,
                                Get $get,
                                ?string $state
                            ) {
                                // Reset term weights if switching away from term-based
                                if (
                                    $state &&
                                    ! in_array($state, Team::COLLEGE_TERM_SCALES)
                                ) {
                                    $set('collegePrelimWeight', null);
                                    $set('collegeMidtermWeight', null);
                                    $set('collegeFinalWeight', null);
                                } elseif (
                                    $state &&
                                    in_array($state, Team::COLLEGE_TERM_SCALES)
                                ) {
                                    // Set default term weights if switching TO term-based and they are null
                                    $set(
                                        'collegePrelimWeight',
                                        $get('collegePrelimWeight') ?? 30
                                    );
                                    $set(
                                        'collegeMidtermWeight',
                                        $get('collegeMidtermWeight') ?? 30
                                    );
                                    $set(
                                        'collegeFinalWeight',
                                        $get('collegeFinalWeight') ?? 40
                                    );
                                }
                                $this->updateTeamGradingSystem(
                                    $get('gradingSystemType'),
                                    $state
                                );
                            }),

                        // --- College Term Weights ---
                        Fieldset::make('College Term Weights (%)')
                            ->schema([
                                TextInput::make('collegePrelimWeight')
                                    ->label('Prelim')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(
                                        fn (
                                            $state
                                        ) => ($this->collegePrelimWeight = (int) $state)
                                    ),
                                TextInput::make('collegeMidtermWeight')
                                    ->label('Midterm')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(
                                        fn (
                                            $state
                                        ) => ($this->collegeMidtermWeight = (int) $state)
                                    ),
                                TextInput::make('collegeFinalWeight')
                                    ->label('Final')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(
                                        fn (
                                            $state
                                        ) => ($this->collegeFinalWeight = (int) $state)
                                    ),
                                Placeholder::make('college_total_weight')
                                    ->label('Total')
                                    ->content(function (Get $get): HtmlString {
                                        $total =
                                            (int) $get('collegePrelimWeight') +
                                            (int) $get('collegeMidtermWeight') +
                                            (int) $get('collegeFinalWeight');
                                        $color =
                                            $total === 100
                                                ? 'text-success-600'
                                                : 'text-danger-600';

                                        return new HtmlString(
                                            "<span class='text-lg font-bold {$color}'>{$total}%</span>"
                                        );
                                    }),
                            ])
                            ->columns(4)
                            ->visible(
                                fn (Get $get): bool => $get(
                                    'gradingSystemType'
                                ) === Team::GRADING_SYSTEM_COLLEGE &&
                                    in_array(
                                        $get('collegeGradingScale'),
                                        Team::COLLEGE_TERM_SCALES
                                    )
                            ), // Show only for term-based college systems
                        // --- SHS Specific (Keep Existing) ---
                        Fieldset::make('SHS Component Weights (%)')
                            ->schema([
                                TextInput::make('shsWrittenWorkWeight')
                                    ->label('Written Work (WW)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500) // Use live() for reactivity
                                    ->afterStateUpdated(
                                        fn (
                                            $state
                                        ) => ($this->shsWrittenWorkWeight = (int) $state)
                                    ) // Update property
                                    ->rules(['integer', 'min:0', 'max:100']), // Add validation rules
                                TextInput::make('shsPerformanceTaskWeight')
                                    ->label('Performance Task (PT)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(
                                        fn (
                                            $state
                                        ) => ($this->shsPerformanceTaskWeight = (int) $state)
                                    )
                                    ->rules(['integer', 'min:0', 'max:100']),
                                TextInput::make('shsQuarterlyAssessmentWeight')
                                    ->label('Quarterly Assessment (QA)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(
                                        fn (
                                            $state
                                        ) => ($this->shsQuarterlyAssessmentWeight = (int) $state)
                                    )
                                    ->rules(['integer', 'min:0', 'max:100']),
                                Placeholder::make('total_weight')
                                    ->label('Total Weight')
                                    ->content(function (Get $get): HtmlString {
                                        $total =
                                            (int) $get('shsWrittenWorkWeight') +
                                            (int) $get(
                                                'shsPerformanceTaskWeight'
                                            ) +
                                            (int) $get(
                                                'shsQuarterlyAssessmentWeight'
                                            );
                                        $color =
                                            $total === 100
                                                ? 'text-success-600'
                                                : 'text-danger-600';

                                        return new HtmlString(
                                            "<span class='text-lg font-bold {$color}'>{$total}%</span>"
                                        );
                                    }),
                            ])
                            ->columns(4)
                            ->visible(
                                fn (Get $get) => $get('gradingSystemType') ===
                                    Team::GRADING_SYSTEM_SHS
                            ),

                        // --- General Display ---
                        Toggle::make('showFinalGrades') // Keep name for now, represents 'Show Overall Grade Column'
                            ->label('Show Overall Grade Column')
                            ->default(true)
                            ->inline(false)
                            ->live() // Make it live
                            ->afterStateUpdated(
                                fn (
                                    $state
                                ) => ($this->showFinalGrades = (bool) $state)
                            ), // Update property
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function saveAllScores(): void
    {
        // Validate weights based on the selected system BEFORE saving scores
        if ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS) {
            $totalShsWeight =
                $this->shsWrittenWorkWeight +
                $this->shsPerformanceTaskWeight +
                $this->shsQuarterlyAssessmentWeight;
            if ($totalShsWeight !== 100) {
                Notification::make()
                    ->title('Cannot Save')
                    ->body('SHS weights must sum to 100%.')
                    ->danger()
                    ->send();

                return;
            }
            // Persist SHS weights if changed
            $this->team->shs_ww_weight = $this->shsWrittenWorkWeight;
            $this->team->shs_pt_weight = $this->shsPerformanceTaskWeight;
            $this->team->shs_qa_weight = $this->shsQuarterlyAssessmentWeight;
        } elseif ($this->team->usesCollegeTermGrading()) {
            $totalTermWeight =
                $this->collegePrelimWeight +
                $this->collegeMidtermWeight +
                $this->collegeFinalWeight;
            if ($totalTermWeight !== 100) {
                Notification::make()
                    ->title('Cannot Save')
                    ->body('College term weights must sum to 100%.')
                    ->danger()
                    ->send();

                return;
            }
            // Persist term weights if changed
            $this->team->college_prelim_weight = $this->collegePrelimWeight;
            $this->team->college_midterm_weight = $this->collegeMidtermWeight;
            $this->team->college_final_weight = $this->collegeFinalWeight;
        }

        // Save team config changes if any occurred during validation checks
        if ($this->team->isDirty()) {
            $this->team->save();
        }

        // Proceed with saving scores (existing logic is fine)
        DB::beginTransaction();
        try {
            // ... (Keep the existing score saving loop with updateOrCreate and validation)
            $updatedCount = 0;
            $userId = Auth::id();
            $now = now();

            foreach ($this->activityScores as $studentId => $scores) {
                foreach ($scores as $activityId => $score) {
                    $activity = $this->activities->firstWhere(
                        'id',
                        $activityId
                    );
                    if (! $activity) {
                        continue;
                    }

                    $scoreValue =
                        $score === '' || $score === null
                            ? null
                            : (float) $score;

                    // Validate score
                    if (
                        $scoreValue !== null &&
                        $activity->total_points !== null &&
                        $scoreValue > $activity->total_points
                    ) {
                        throw ValidationException::withMessages([
                            "activityScores.{$studentId}.{$activityId}" => "Max: {$activity->total_points}",
                        ]);
                    }
                    if ($scoreValue !== null && $scoreValue < 0) {
                        throw ValidationException::withMessages([
                            "activityScores.{$studentId}.{$activityId}" => 'Min: 0',
                        ]);
                    }

                    $submission = ActivitySubmission::updateOrCreate(
                        [
                            'activity_id' => $activityId,
                            'student_id' => $studentId,
                        ],
                        [
                            'score' => $scoreValue,
                            'status' => $scoreValue !== null
                                    ? 'graded'
                                    : $submission->status ?? 'pending',
                            'graded_by' => $scoreValue !== null
                                    ? $userId
                                    : $submission->graded_by ?? null,
                            'graded_at' => $scoreValue !== null
                                    ? $now
                                    : $submission->graded_at ?? null,
                            'final_grade' => null, // Ensure final_grade is always cleared/null
                        ]
                    );
                    if (
                        $submission->wasChanged(['score']) ||
                        $submission->wasRecentlyCreated
                    ) {
                        $updatedCount++;
                    }
                }
            }

            DB::commit();
            Notification::make()
                ->title('Grades Saved')
                ->body("Successfully saved/updated {$updatedCount} scores.")
                ->success()
                ->send();
            $this->loadActivityScores(); // Reload and recalculate grades
        } catch (ValidationException $e) {
            DB::rollBack();
            Notification::make()
                ->title('Validation Error')
                ->body('Please correct score errors.')
                ->danger()
                ->send();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Error saving scores: '.
                    $e->getMessage().
                    "\n".
                    $e->getTraceAsString()
            );
            Notification::make()
                ->title('Error')
                ->body('Error saving scores.')
                ->danger()
                ->send();
        }
    }

    /**
     * Update a single score for a student's activity
     *
     * @deprecated Use saveAllScores() instead
     */
    public function updateScore($studentId, $activityId): void
    {
        try {
            // Access the score directly from the activityScores array
            $score = $this->activityScores[$studentId][$activityId] ?? null;

            Log::info(
                "Updating score for student $studentId, activity $activityId: $score"
            );

            $submission = ActivitySubmission::firstOrNew([
                'activity_id' => $activityId,
                'student_id' => $studentId,
            ]);

            $submission->score =
                $score !== '' && $score !== null ? (float) $score : null;

            if (! $submission->exists || $submission->score !== null) {
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

            $finalGrade = $this->calculateFinalGradeValue(
                $studentId,
                $activities
            );

            if ($finalGrade !== null) {
                // Update all submissions for this student with the final grade
                ActivitySubmission::where('student_id', $studentId)
                    ->whereIn('activity_id', $activities->pluck('id'))
                    ->update(['final_grade' => $finalGrade]);
            }

            Log::info(
                "Score updated successfully: submission ID $submission->id"
            );

            // Send Filament notification
            Notification::make()
                ->title('Score Updated')
                ->body(
                    "Score updated for {$submission->student->name} on {$submission->activity->title}."
                )
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error updating score: '.$e->getMessage());
            Notification::make()
                ->title('Error')
                ->body('Error updating score. Please try again.')
                ->danger()
                ->send();
        }
    }

    // --- Update Team Settings ---
    protected function updateTeamGradingSystem(
        ?string $systemType,
        ?string $collegeScale
    ): void {
        if (! $this->team) {
            return;
        }

        $this->team->grading_system_type = $systemType;
        $this->gradingSystemType = $systemType;

        if ($systemType === Team::GRADING_SYSTEM_COLLEGE) {
            $this->team->college_grading_scale = $collegeScale;
            $this->collegeGradingScale = $collegeScale;
            // Clear SHS weights
            $this->team->shs_ww_weight = null;
            $this->team->shs_pt_weight = null;
            $this->team->shs_qa_weight = null;

            // Handle College Term Weights
            if (in_array($collegeScale, Team::COLLEGE_TERM_SCALES)) {
                // Validate and save term weights
                $totalTermWeight =
                    $this->collegePrelimWeight +
                    $this->collegeMidtermWeight +
                    $this->collegeFinalWeight;
                if ($totalTermWeight !== 100) {
                    Notification::make()
                        ->title('Invalid Weights')
                        ->body('College term weights must sum to 100%.')
                        ->danger()
                        ->send();
                    // Don't save invalid weights, but allow system type change
                } else {
                    $this->team->college_prelim_weight =
                        $this->collegePrelimWeight;
                    $this->team->college_midterm_weight =
                        $this->collegeMidtermWeight;
                    $this->team->college_final_weight =
                        $this->collegeFinalWeight;
                }
            } else {
                // Clear term weights if not using term system
                $this->team->college_prelim_weight = null;
                $this->team->college_midterm_weight = null;
                $this->team->college_final_weight = null;
            }
        } elseif ($systemType === Team::GRADING_SYSTEM_SHS) {
            // Clear college settings
            $this->team->college_grading_scale = null;
            $this->collegeGradingScale = null;
            $this->team->college_prelim_weight = null;
            $this->team->college_midterm_weight = null;
            $this->team->college_final_weight = null;
            // Validate and Save SHS weights
            $totalShsWeight =
                $this->shsWrittenWorkWeight +
                $this->shsPerformanceTaskWeight +
                $this->shsQuarterlyAssessmentWeight;
            if ($totalShsWeight !== 100) {
                Notification::make()
                    ->title('Invalid Weights')
                    ->body('SHS component weights must sum to 100%.')
                    ->danger()
                    ->send();
            } else {
                $this->team->shs_ww_weight = $this->shsWrittenWorkWeight;
                $this->team->shs_pt_weight = $this->shsPerformanceTaskWeight;
                $this->team->shs_qa_weight =
                    $this->shsQuarterlyAssessmentWeight;
            }
        } else {
            // Clear all specific settings if type is null/other
            $this->team->college_grading_scale = null;
            $this->team->shs_ww_weight = null; /* ... etc ... */
            $this->team->college_prelim_weight = null; /* ... etc ... */
        }

        if ($this->team->isDirty()) {
            $this->team->save();
            Notification::make()
                ->title('Settings Updated')
                ->body('Grading system configuration saved.')
                ->success()
                ->send();
            $this->calculateAllStudentOverallGrades(); // Recalculate
        }
    }

    /**
     * Calculate the overall grade value for a single student.
     * Returns an array: ['final_grade' => float|null, 'term_grades' => array|null]
     */
    protected function calculateStudentOverallGradeValue(
        string $studentId
    ): array {
        $studentScores = $this->activityScores[$studentId] ?? [];
        $defaultResult = ['final_grade' => null, 'term_grades' => null];

        if (
            empty($studentScores) ||
            $this->activities->isEmpty() ||
            ! $this->gradingSystemType
        ) {
            return $defaultResult;
        }

        try {
            if ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS) {
                $initialGrade = $this->gradingService->calculateShsInitialGrade(
                    $studentScores,
                    $this->activities,
                    $this->shsWrittenWorkWeight,
                    $this->shsPerformanceTaskWeight,
                    $this->shsQuarterlyAssessmentWeight
                );

                // For SHS, 'final_grade' holds the initial grade before transmutation for display logic
                return ['final_grade' => $initialGrade, 'term_grades' => null];
            } elseif (
                $this->gradingSystemType === Team::GRADING_SYSTEM_COLLEGE
            ) {
                $numericScale = $this->team->getCollegeNumericScale(); // Get '5_point', '4_point', etc.

                if ($this->team->usesCollegeTermGrading()) {
                    // Calculate College Final Final Grade (Term-Based)
                    return $this->gradingService->calculateCollegeFinalFinalGrade(
                        $studentScores,
                        $this->activities,
                        $this->collegePrelimWeight,
                        $this->collegeMidtermWeight,
                        $this->collegeFinalWeight,
                        $numericScale
                    );
                } elseif ($this->team->usesCollegeGwaGrading()) {
                    // Calculate College GWA
                    $gwa = $this->gradingService->calculateCollegeGwa(
                        $studentScores,
                        $this->activities,
                        $numericScale // Pass numeric scale only
                    );

                    return ['final_grade' => $gwa, 'term_grades' => null];
                } else {
                    // College type selected but scale/subtype configuration is incomplete
                    return $defaultResult;
                }
            }
        } catch (\Exception $e) {
            Log::error(
                "Error calculating grade for student {$studentId}: ".
                    $e->getMessage()
            );

            return $defaultResult;
        }

        return $defaultResult;
    }

    /**
     * Get the formatted overall grade display string for a student.
     */
    public function getFormattedOverallGrade(
        string $studentId
    ): HtmlString|string {
        $overallGradeValue = $this->studentOverallGrades[$studentId] ?? null;
        $numericScale = $this->team?->getCollegeNumericScale(); // Needed for college formatting

        if ($overallGradeValue === null) {
            return new HtmlString(
                '<span class="text-gray-400 dark:text-gray-500">N/A</span>'
            );
        }

        if ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS) {
            // Transmute the SHS Initial Grade
            $transmutedGrade = $this->gradingService->transmuteShsGrade(
                $overallGradeValue
            ); // $overallGradeValue IS the initial grade here
            $description = $this->gradingService->getShsGradeDescriptor(
                $transmutedGrade
            );
            $color = $this->gradingService->getShsGradeColor($transmutedGrade);

            return new HtmlString/* ... same SHS HTML ... */; // Keep existing SHS display logic
        } elseif (
            $this->gradingSystemType === Team::GRADING_SYSTEM_COLLEGE &&
            $numericScale
        ) {
            // Display College GWA or Final Term Grade
            $formattedGrade = $this->gradingService->formatCollegeGrade(
                $overallGradeValue,
                $numericScale
            );
            $color = $this->gradingService->getCollegeGradeColor(
                $overallGradeValue,
                $numericScale
            );

            return new HtmlString(
                "<span class='{$color} font-bold text-lg'>{$formattedGrade}</span>"
            );
        }

        // Fallback
        return new HtmlString(
            '<span class="text-gray-400 dark:text-gray-500">N/A</span>'
        );
    }

    public function calculateAllStudentOverallGrades(): void
    {
        if (
            ! $this->students ||
            ! $this->activities ||
            ! $this->gradingSystemType
        ) {
            $this->studentOverallGrades = [];
            $this->studentTermGrades = []; // Clear term grades too

            return;
        }

        $newOverallGrades = [];
        $newTermGrades = []; // Store term grades temporarily

        foreach ($this->students as $student) {
            $overallResult = $this->calculateStudentOverallGradeValue(
                $student->id
            );
            $newOverallGrades[$student->id] = $overallResult['final_grade']; // Store final grade
            // Store term grades if calculated (only for term-based college)
            if (! empty($overallResult['term_grades'])) {
                $newTermGrades[$student->id] = $overallResult['term_grades'];
            }
        }
        $this->studentOverallGrades = $newOverallGrades;
        $this->studentTermGrades = $newTermGrades; // Assign term grades

        $this->dispatch('gradesCalculated');
    }

    protected function calculateCategoryAverage(
        string $studentId,
        Collection $activities,
        string $category
    ): string {
        $categoryActivities = $activities->filter(function ($activity) use (
            $category
        ) {
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

        return number_format($average, 2).'%';
    }

    protected function calculateFinalGrade(
        string $studentId,
        Collection $activities
    ): string {
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

        return "<span class=\"{$color} font-bold\">".
            number_format($finalGrade, 2).
            '%</span>';
    }

    protected function calculateFinalGradeValue(
        string $studentId,
        Collection $activities
    ): ?float {
        if ($activities->isEmpty()) {
            return null;
        }

        $writtenActivities = $activities->filter(
            fn ($activity) => $activity->category === 'written'
        );
        $performanceActivities = $activities->filter(
            fn ($activity) => $activity->category === 'performance'
        );

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

        $writtenPercentage =
            $writtenTotal > 0 ? ($writtenEarned / $writtenTotal) * 100 : 0;
        $performancePercentage =
            $performanceTotal > 0
                ? ($performanceEarned / $performanceTotal) * 100
                : 0;

        $writtenWeight = $this->writtenWeight / 100;
        $performanceWeight = $this->performanceWeight / 100;

        $finalGrade =
            $writtenPercentage * $writtenWeight +
            $performancePercentage * $performanceWeight;

        return round($finalGrade, 2);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Removed 'Calculate Final Grades' as it's now dynamic or part of Save
            Action::make('export')
                ->label('Export Grades')
                ->icon('heroicon-o-arrow-down-tray')
                ->size(ActionSize::Large)
                ->color('success')
                ->action(fn () => $this->exportGrades()),
        ];
    }

    // --- Export (Adjust Headers) ---
    protected function exportGrades()
    {
        if (
            ! $this->team ||
            $this->students->isEmpty() ||
            $this->activities->isEmpty()
        ) {
            return null; // Or show notification
        }

        $headers = ['Student Name'];
        $isShs = $this->team->usesShsGrading();
        $isCollegeTerm = $this->team->usesCollegeTermGrading();
        $isCollegeGwa = $this->team->usesCollegeGwaGrading();
        $numericScale = $this->team->getCollegeNumericScale();

        foreach ($this->activities as $activity) {
            $header = '';
            if ($isShs && $activity->component_type) {
                $header .= '['.$activity->component_type_code.'] ';
            } elseif ($isCollegeTerm && $activity->term) {
                $header .= '['.$activity->getTermCode().'] '; // e.g., [PRE]
            }
            $header .= $activity->title;
            $header .= ' ('.($activity->total_points ?? 'N/A').' pts';
            if ($isCollegeGwa && $activity->credit_units > 0) {
                $header .= ' / '.$activity->credit_units.' units';
            }
            $header .= ')';
            $headers[] = $header;
        }

        if ($this->showFinalGrades) {
            $headers[] = $isShs ? 'Overall (Transmuted)' : 'Final Grade';
        }

        $data = [];
        $data[] = $headers;

        foreach ($this->students as $student) {
            $row = [$student->name];
            $studentScores = $this->activityScores[$student->id] ?? [];

            foreach ($this->activities as $activity) {
                $row[] = $studentScores[$activity->id] ?? '';
            }

            if ($this->showFinalGrades) {
                $overallGradeValue =
                    $this->studentOverallGrades[$student->id] ?? null;
                $formattedOverall = 'N/A';
                if ($overallGradeValue !== null) {
                    if ($isShs) {
                        $formattedOverall = $this->gradingService->transmuteShsGrade(
                            $overallGradeValue
                        ); // Transmuted for export
                    } elseif (
                        ($isCollegeTerm || $isCollegeGwa) &&
                        $numericScale
                    ) {
                        $formattedOverall = $this->gradingService->formatCollegeGrade(
                            $overallGradeValue,
                            $numericScale,
                            true
                        ); // Raw format
                    } else {
                        $formattedOverall = number_format(
                            $overallGradeValue,
                            2
                        ); // Generic fallback
                    }
                }
                $row[] = $formattedOverall;
            }
            $data[] = $row;
        }

        // ... (CSV generation logic remains the same) ...
        $callback = function () {
            /* ... */
        };
        $filename =
            Str::slug(
                $this->team->name.' Gradesheet '.now()->format('Y-m-d')
            ).'.csv';

        return Response::stream($callback, 200, [
            /* ... headers ... */
        ]);
    }

    public function openFinalGradeModal(string $studentId): void
    {
        // Ensure data is up-to-date before opening modal
        $this->calculateAllStudentOverallGrades();
        // Dispatch event for AlpineJS/Livewire listener
        $this->dispatch(
            'open-modal',
            id: 'final-grade-modal',
            studentId: $studentId
        );
    }

    public function getStudentGradeBreakdown(string $studentId): array
    {
        $student = $this->students->firstWhere('id', $studentId);
        if (! $student) {
            return ['error' => 'Student not found'];
        }

        $studentScores = $this->activityScores[$studentId] ?? [];
        $overallGradeValue = $this->studentOverallGrades[$studentId] ?? null;
        $termGrades = $this->studentTermGrades[$studentId] ?? null;
        $numericScale = $this->team?->getCollegeNumericScale(); // Get numeric scale once

        $breakdown = [];
        $activitiesData = [];

        // --- Prepare Detailed Activity Data (Keep existing loop) ---
        foreach ($this->activities as $activity) {
            // ... (keep existing logic to populate $activitiesData)
            $score = $studentScores[$activity->id] ?? null;
            $percentage = null;
            if ($score !== null && $activity->total_points > 0) {
                $percentage = round(
                    ((float) $score / (float) $activity->total_points) * 100,
                    2
                );
            }
            // Add formatted percentage and color based on scale
            $activityFormattedPercentage =
                $percentage !== null
                    ? number_format($percentage, 1).'%'
                    : '-';
            $activityColor = 'text-gray-500 dark:text-gray-400';
            if ($percentage !== null && $numericScale) {
                // Use numericScale for consistency
                $tempScaleGrade = $this->gradingService->convertPercentageToCollegeScale(
                    $percentage,
                    $numericScale
                );
                $activityColor = $this->gradingService->getCollegeGradeColor(
                    $tempScaleGrade,
                    $numericScale
                );
            } elseif ($percentage !== null && $this->team->usesShsGrading()) {
                // SHS Specific Percentage Coloring
                if ($percentage >= 90) {
                    $activityColor = 'text-success-600 dark:text-success-400';
                } elseif ($percentage >= 75) {
                    $activityColor = 'text-warning-600 dark:text-warning-400';
                } else {
                    $activityColor = 'text-danger-600 dark:text-danger-400';
                }
            }

            $activitiesData[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'component_type' => $activity->component_type,
                'component_code' => $activity->component_type_code,
                'term' => $activity->term,
                'term_code' => $activity->getTermCode(),
                'total_points' => $activity->total_points,
                'credit_units' => $activity->credit_units,
                'score' => $score,
                'percentage' => $percentage, // Keep raw percentage if needed
                'formatted_percentage' => $activityFormattedPercentage, // Add formatted
                'color_class' => $activityColor, // Add color class
            ];
        }

        // --- Generate Breakdown with Formatted Data ---
        if ($this->team->usesShsGrading()) {
            $initialGrade = $overallGradeValue; // It holds the initial grade
            $transmutedGrade =
                $initialGrade !== null
                    ? $this->gradingService->transmuteShsGrade($initialGrade)
                    : null;
            $descriptor =
                $transmutedGrade !== null
                    ? $this->gradingService->getShsGradeDescriptor(
                        $transmutedGrade
                    )
                    : 'N/A';
            $colorClass =
                $transmutedGrade !== null
                    ? $this->gradingService->getShsGradeColor($transmutedGrade)
                    : 'text-gray-400';

            // Recalculate component details for the modal
            $wwDetails = $this->gradingService->calculateShsComponentDetails(
                $studentScores,
                $this->activities,
                Activity::COMPONENT_WRITTEN_WORK
            );
            $ptDetails = $this->gradingService->calculateShsComponentDetails(
                $studentScores,
                $this->activities,
                Activity::COMPONENT_PERFORMANCE_TASK
            );
            $qaDetails = $this->gradingService->calculateShsComponentDetails(
                $studentScores,
                $this->activities,
                Activity::COMPONENT_QUARTERLY_ASSESSMENT
            );

            // Add formatted scores to component details
            $formatComponent = function ($details, $weight) {
                $ps = $details['percentage_score'];
                $details['formatted_ps'] =
                    $ps !== null ? number_format($ps, 2).'%' : 'N/A';
                $details['formatted_ws'] =
                    $ps !== null
                        ? number_format($ps * ($weight / 100), 2)
                        : 'N/A';
                $details['formatted_raw'] =
                    $details['total_raw_score'] !== null
                        ? $details['total_raw_score'].
                            ' / '.
                            $details['total_highest_possible_score']
                        : 'N/A';

                return $details;
            };

            $breakdown = [
                'type' => 'shs',
                'weights' => [
                    'ww' => $this->shsWrittenWorkWeight,
                    'pt' => $this->shsPerformanceTaskWeight,
                    'qa' => $this->shsQuarterlyAssessmentWeight,
                ],
                'components' => [
                    // Add formatted data here
                    'ww' => $formatComponent(
                        $wwDetails,
                        $this->shsWrittenWorkWeight
                    ),
                    'pt' => $formatComponent(
                        $ptDetails,
                        $this->shsPerformanceTaskWeight
                    ),
                    'qa' => $formatComponent(
                        $qaDetails,
                        $this->shsQuarterlyAssessmentWeight
                    ),
                ],
                'initial_grade' => $initialGrade,
                'formatted_initial_grade' => $initialGrade !== null
                        ? number_format($initialGrade, 2)
                        : 'N/A',
                'transmuted_grade' => $transmutedGrade, // Keep raw value
                'formatted_transmuted_grade' => $transmutedGrade ?? 'N/A', // Formatted (just the number)
                'descriptor' => $descriptor,
                'color_class' => $colorClass, // Pass color class
            ];
        } elseif ($this->team->usesCollegeTermGrading() && $numericScale) {
            // Format term grades and final grade
            $formattedTermGrades = [];
            $termGradeColors = [];
            foreach ($termGrades ?? [] as $termKey => $grade) {
                $formattedTermGrades[$termKey] =
                    $grade !== null
                        ? $this->gradingService->formatCollegeGrade(
                            $grade,
                            $numericScale
                        )
                        : 'N/A';
                $termGradeColors[$termKey] =
                    $grade !== null
                        ? $this->gradingService->getCollegeGradeColor(
                            $grade,
                            $numericScale
                        )
                        : 'text-gray-400';
            }

            $formattedFinalGrade =
                $overallGradeValue !== null
                    ? $this->gradingService->formatCollegeGrade(
                        $overallGradeValue,
                        $numericScale
                    )
                    : 'N/A';
            $finalGradeColor =
                $overallGradeValue !== null
                    ? $this->gradingService->getCollegeGradeColor(
                        $overallGradeValue,
                        $numericScale
                    )
                    : 'text-gray-400';

            $breakdown = [
                'type' => 'college_term',
                'scale' => $numericScale,
                'scale_description' => $this->team->grading_system_description,
                'weights' => [
                    'prelim' => $this->collegePrelimWeight,
                    'midterm' => $this->collegeMidtermWeight,
                    'final' => $this->collegeFinalWeight,
                ],
                'term_grades' => $termGrades, // Keep raw term grades if needed
                'formatted_term_grades' => $formattedTermGrades, // Add formatted
                'term_grade_colors' => $termGradeColors, // Add colors
                'final_grade' => $overallGradeValue, // Keep raw final grade
                'formatted_final_grade' => $formattedFinalGrade, // Add formatted
                'final_grade_color' => $finalGradeColor, // Add final color
            ];
        } elseif ($this->team->usesCollegeGwaGrading() && $numericScale) {
            $collegeGradeDetails = $this->gradingService->calculateCollegeGwaDetails(
                $studentScores,
                $this->activities,
                $numericScale
            );
            $formattedGwa =
                $overallGradeValue !== null
                    ? $this->gradingService->formatCollegeGrade(
                        $overallGradeValue,
                        $numericScale
                    )
                    : 'N/A';
            $gwaColor =
                $overallGradeValue !== null
                    ? $this->gradingService->getCollegeGradeColor(
                        $overallGradeValue,
                        $numericScale
                    )
                    : 'text-gray-400';
            // Format activity scale grades included in GWA
            $formattedActivityGrades = [];
            foreach (
                $collegeGradeDetails['activity_grades'] ?? [] as $actId => $gradeInfo
            ) {
                $formattedActivityGrades[$actId] = $gradeInfo; // Copy existing
                $formattedActivityGrades[$actId][
                    'formatted_scale_grade'
                ] = $this->gradingService->formatCollegeGrade(
                    $gradeInfo['scale_grade'],
                    $numericScale
                );
                $formattedActivityGrades[$actId][
                    'color_class'
                ] = $this->gradingService->getCollegeGradeColor(
                    $gradeInfo['scale_grade'],
                    $numericScale
                );
                $formattedActivityGrades[$actId][
                    'formatted_weighted_part'
                ] = number_format(
                    $gradeInfo['scale_grade'] * $gradeInfo['units'],
                    2
                );
            }

            $breakdown = [
                'type' => 'college_gwa',
                'scale' => $numericScale,
                'scale_description' => $this->team->grading_system_description,
                'gwa' => $overallGradeValue, // Raw GWA
                'formatted_gwa' => $formattedGwa, // Formatted GWA
                'gwa_color' => $gwaColor, // GWA Color
                'total_units' => $collegeGradeDetails['total_units'] ?? 0,
                'formatted_total_units' => number_format(
                    $collegeGradeDetails['total_units'] ?? 0,
                    2
                ),
                'weighted_grade_sum' => $collegeGradeDetails['weighted_grade_sum'] ?? 0,
                'formatted_weighted_grade_sum' => number_format(
                    $collegeGradeDetails['weighted_grade_sum'] ?? 0,
                    2
                ),
                'activity_grades' => $formattedActivityGrades, // Pass formatted activity grades
            ];
        } else {
            $breakdown = ['type' => 'none'];
        }

        return [
            'student' => $student->only(['id', 'name']),
            'activities' => $activitiesData,
            'breakdown' => $breakdown,
        ];
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
            $scores[$activity->id] =
                $this->activityScores[$studentId][$activity->id] ?? null;
        }

        return [
            'student' => $student,
            'activities' => $activities,
            'scores' => $scores,
        ];
    }

    /**
     * Get the formatted grade for a specific term for a student.
     */
    public function getFormattedTermGrade(string $studentId, string $termKey): HtmlString|string
    {
        $termGradeValue = $this->studentTermGrades[$studentId][$termKey] ?? null;
        $numericScale = $this->team?->getCollegeNumericScale();

        if ($termGradeValue === null || ! $numericScale) {
            return new HtmlString('<span class="text-gray-400 dark:text-gray-500">-</span>');
        }

        $formattedGrade = $this->gradingService->formatCollegeGrade($termGradeValue, $numericScale);
        $color = $this->gradingService->getCollegeGradeColor($termGradeValue, $numericScale);

        // Add tooltip with raw value if needed
        $rawValue = number_format($termGradeValue, 2);
        $title = "{$termKey} Grade: {$rawValue}";

        return new HtmlString("<span class='{$color} font-semibold' title='{$title}'>{$formattedGrade}</span>");
    }

    // Helper to get term background color for table headers
    public function getTermHeaderClass(string $term): string
    {
        return match ($term) {
            Activity::TERM_PRELIM => 'bg-teal-50 dark:bg-teal-900/50',
            Activity::TERM_MIDTERM => 'bg-purple-50 dark:bg-purple-900/50',
            Activity::TERM_FINAL => 'bg-orange-50 dark:bg-orange-900/50',
            default => 'bg-gray-50 dark:bg-white/5', // Fallback
        };
    }

    // Helper to get SHS component background color for table headers
    public function getShsComponentHeaderClass(string $componentType): string
    {
        return match ($componentType) {
            Activity::COMPONENT_WRITTEN_WORK => 'bg-blue-50 dark:bg-blue-900/50',
            Activity::COMPONENT_PERFORMANCE_TASK => 'bg-red-50 dark:bg-red-900/50',
            Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'bg-yellow-50 dark:bg-yellow-900/50',
            default => 'bg-gray-50 dark:bg-white/5', // Fallback
        };
    }
}
