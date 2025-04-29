<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Student;
use App\Models\Team;
use App\Services\GradingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
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
use Illuminate\Support\HtmlString;
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

    // College Term (Overall) Weights
    public ?int $collegePrelimWeight = null;
    public ?int $collegeMidtermWeight = null;
    public ?int $collegeFinalWeight = null;

    // NEW: College Term COMPONENT Weights (WW/PT within each term)
    public ?int $collegeTermWwWeight = null;
    public ?int $collegeTermPtWeight = null;

    public array $studentTermGrades = []; // Stores calculated term grades (prelim, midterm, final)

    // Display Toggle
    public bool $showFinalGrades = true; // Represents 'Show Overall Grade Column'

    // Store activity scores (student_id => activity_id => score)
    public array $activityScores = [];

    // Store calculated overall grades (student_id => final_grade)
    public array $studentOverallGrades = [];

    // Store fetched activities and students
    public Collection $students;
    public Collection $activities;

    protected GradingService $gradingService;

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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationItems(): array
    {
        if (!static::canAccess()) {
            return [];
        }
        return parent::getNavigationItems();
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            Notification::make()
                ->title('Error')
                ->body('No active team selected.')
                ->danger()
                ->send();
            redirect()->route('filament.app.pages.dashboard')->send();
            exit();
        }

        $this->team = $user->currentTeam;
        $this->teamId = $this->team->id;
        $this->gradingSystemType = $this->team->grading_system_type;
        $this->collegeGradingScale = $this->team->college_grading_scale;

        if (!$this->team->userIsOwner($user)) {
            Notification::make()
                ->title('Access Denied')
                ->body('Only team owners can access the gradesheet.')
                ->danger()
                ->send();
            redirect()->route('filament.app.pages.dashboard', ['tenant' => $this->teamId])->send();
            exit();
        }

        // Initialize Overall Term Weights
        $this->collegePrelimWeight = $this->team->college_prelim_weight ?? ($this->team->usesCollegeTermGrading() ? 30 : null);
        $this->collegeMidtermWeight = $this->team->college_midterm_weight ?? ($this->team->usesCollegeTermGrading() ? 30 : null);
        $this->collegeFinalWeight = $this->team->college_final_weight ?? ($this->team->usesCollegeTermGrading() ? 40 : null);

        // Initialize SHS Component Weights
        $this->shsWrittenWorkWeight = $this->team->shs_ww_weight ?? ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS ? 30 : null);
        $this->shsPerformanceTaskWeight = $this->team->shs_pt_weight ?? ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS ? 50 : null);
        $this->shsQuarterlyAssessmentWeight = $this->team->shs_qa_weight ?? ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS ? 20 : null);

        // Initialize College Term COMPONENT Weights (New)
        $this->collegeTermWwWeight = $this->team->college_term_ww_weight ?? ($this->team->usesCollegeTermGrading() ? 40 : null); // Default 40%
        $this->collegeTermPtWeight = $this->team->college_term_pt_weight ?? ($this->team->usesCollegeTermGrading() ? 60 : null); // Default 60%

        $this->loadInitialData();
        $this->calculateAllStudentOverallGrades();

        // Fill form with current settings
        $this->form->fill([
            'gradingSystemType' => $this->gradingSystemType,
            'collegeGradingScale' => $this->collegeGradingScale,
            // SHS Weights
            'shsWrittenWorkWeight' => $this->shsWrittenWorkWeight,
            'shsPerformanceTaskWeight' => $this->shsPerformanceTaskWeight,
            'shsQuarterlyAssessmentWeight' => $this->shsQuarterlyAssessmentWeight,
            // College Term (Overall) Weights
            'collegePrelimWeight' => $this->collegePrelimWeight,
            'collegeMidtermWeight' => $this->collegeMidtermWeight,
            'collegeFinalWeight' => $this->collegeFinalWeight,
            // College Term Component Weights (New)
            'collegeTermWwWeight' => $this->collegeTermWwWeight,
            'collegeTermPtWeight' => $this->collegeTermPtWeight,
            // Display Toggle
            'showFinalGrades' => $this->showFinalGrades,
        ]);
    }

    protected function loadActivityScores(): void
    {
        if (!$this->students || !$this->activities) {
            return;
        }

        $submissions = ActivitySubmission::whereIn('student_id', $this->students->pluck('id'))
            ->whereIn('activity_id', $this->activities->pluck('id'))
            ->get()
            ->keyBy(fn ($item) => $item->student_id . '-' . $item->activity_id);

        $newScores = [];
        foreach ($this->students as $student) {
            foreach ($this->activities as $activity) {
                $key = $student->id . '-' . $activity->id;
                $newScores[$student->id][$activity->id] = $submissions->has($key)
                    ? $submissions[$key]->score
                    : null;
            }
        }
        $this->activityScores = $newScores;
        $this->calculateAllStudentOverallGrades();
    }

    protected function loadInitialData(): void
    {
        $this->students = Student::where('team_id', $this->teamId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $activitiesQuery = Activity::where('team_id', $this->teamId)->where('status', 'published');

        if ($this->team->usesCollegeTermGrading()) {
            $activitiesQuery->orderByRaw(
                '
                    CASE term
                        WHEN ? THEN 1
                        WHEN ? THEN 2
                        WHEN ? THEN 3
                        ELSE 4
                    END ASC
                ',
                [Activity::TERM_PRELIM, Activity::TERM_MIDTERM, Activity::TERM_FINAL]
            );
        } elseif ($this->team->usesShsGrading()) {
            $activitiesQuery->orderByRaw(
                '
                    CASE component_type
                        WHEN ? THEN 1
                        WHEN ? THEN 2
                        WHEN ? THEN 3
                        ELSE 4
                    END ASC
                ',
                [Activity::COMPONENT_WRITTEN_WORK, Activity::COMPONENT_PERFORMANCE_TASK, Activity::COMPONENT_QUARTERLY_ASSESSMENT]
            );
        }
        $activitiesQuery->orderBy('created_at', 'asc');

        $this->activities = $activitiesQuery->get();
        $this->loadActivityScores();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Grading System Configuration')
                    ->description('Select and configure the grading system for this team.')
                    ->schema([
                        Select::make('gradingSystemType')
                            ->label('Grading System')
                            ->options([
                                Team::GRADING_SYSTEM_SHS => 'K-12 SHS (Written, Performance, Quarterly)',
                                Team::GRADING_SYSTEM_COLLEGE => 'College/University',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                $set('collegeGradingScale', null);
                                $set('collegePrelimWeight', null);
                                $set('collegeMidtermWeight', null);
                                $set('collegeFinalWeight', null);
                                $set('collegeTermWwWeight', null); // Reset new weights
                                $set('collegeTermPtWeight', null); // Reset new weights

                                $isShs = $state === Team::GRADING_SYSTEM_SHS;
                                $set('shsWrittenWorkWeight', $isShs ? 30 : null);
                                $set('shsPerformanceTaskWeight', $isShs ? 50 : null);
                                $set('shsQuarterlyAssessmentWeight', $isShs ? 20 : null);
                                $this->updateTeamGradingSystem($state, null);
                            }),

                        // --- College Specific ---
                        Select::make('collegeGradingScale')
                            ->label('College Calculation & Scale')
                            ->options([
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
                            ->visible(fn (Get $get) => $get('gradingSystemType') === Team::GRADING_SYSTEM_COLLEGE)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                $isTermBased = $state && in_array($state, Team::COLLEGE_TERM_SCALES);
                                
                                // Set defaults for Overall Term Weights if switching TO Term-Based
                                $set('collegePrelimWeight', $isTermBased ? ($get('collegePrelimWeight') ?? 30) : null);
                                $set('collegeMidtermWeight', $isTermBased ? ($get('collegeMidtermWeight') ?? 30) : null);
                                $set('collegeFinalWeight', $isTermBased ? ($get('collegeFinalWeight') ?? 40) : null);
                                
                                // Set defaults for Term Component Weights if switching TO Term-Based
                                $set('collegeTermWwWeight', $isTermBased ? ($get('collegeTermWwWeight') ?? 40) : null);
                                $set('collegeTermPtWeight', $isTermBased ? ($get('collegeTermPtWeight') ?? 60) : null);

                                $this->updateTeamGradingSystem($get('gradingSystemType'), $state);
                            }),
                        
                        // --- NEW: College Term COMPONENT Weights (WW/PT within each term) ---
                        Fieldset::make('College Term Component Weights (%)')
                            ->schema([
                                TextInput::make('collegeTermWwWeight')
                                    ->label('Written Work (WW)')
                                    ->helperText('Weight for WW activities within each term.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->collegeTermWwWeight = (int) $state)),
                                TextInput::make('collegeTermPtWeight')
                                    ->label('Performance Task (PT)')
                                    ->helperText('Weight for PT activities within each term.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->collegeTermPtWeight = (int) $state)),
                                Placeholder::make('college_term_component_total_weight')
                                    ->label('Total')
                                    ->content(function (Get $get): HtmlString {
                                        $total = (int) $get('collegeTermWwWeight') + (int) $get('collegeTermPtWeight');
                                        $color = $total === 100 ? 'text-success-600' : 'text-danger-600';
                                        return new HtmlString("<span class='text-lg font-bold {$color}'>{$total}%</span>");
                                    }),
                            ])
                            ->columns(3)
                            ->visible(fn (Get $get): bool => 
                                $get('gradingSystemType') === Team::GRADING_SYSTEM_COLLEGE &&
                                in_array($get('collegeGradingScale'), Team::COLLEGE_TERM_SCALES)
                            ), 

                        // --- College Term Weights (Overall) ---
                        Fieldset::make('College Overall Term Weights (%)') // Renamed for clarity
                            ->schema([
                                TextInput::make('collegePrelimWeight')
                                    ->label('Prelim')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->collegePrelimWeight = (int) $state)),
                                TextInput::make('collegeMidtermWeight')
                                    ->label('Midterm')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->collegeMidtermWeight = (int) $state)),
                                TextInput::make('collegeFinalWeight')
                                    ->label('Final')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->collegeFinalWeight = (int) $state)),
                                Placeholder::make('college_total_weight')
                                    ->label('Total')
                                    ->content(function (Get $get): HtmlString {
                                        $total = (int) $get('collegePrelimWeight') + (int) $get('collegeMidtermWeight') + (int) $get('collegeFinalWeight');
                                        $color = $total === 100 ? 'text-success-600' : 'text-danger-600';
                                        return new HtmlString("<span class='text-lg font-bold {$color}'>{$total}%</span>");
                                    }),
                            ])
                            ->columns(4)
                            ->visible(fn (Get $get): bool => 
                                $get('gradingSystemType') === Team::GRADING_SYSTEM_COLLEGE &&
                                in_array($get('collegeGradingScale'), Team::COLLEGE_TERM_SCALES)
                            ),
                            
                        // --- SHS Specific ---
                        Fieldset::make('SHS Component Weights (%)')
                            ->schema([
                                TextInput::make('shsWrittenWorkWeight')
                                    ->label('Written Work (WW)')
                                    ->numeric()
                                    ->minValue(0)->maxValue(100)->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->shsWrittenWorkWeight = (int) $state))
                                    ->rules(['integer', 'min:0', 'max:100']),
                                TextInput::make('shsPerformanceTaskWeight')
                                    ->label('Performance Task (PT)')
                                    ->numeric()
                                    ->minValue(0)->maxValue(100)->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->shsPerformanceTaskWeight = (int) $state))
                                    ->rules(['integer', 'min:0', 'max:100']),
                                TextInput::make('shsQuarterlyAssessmentWeight')
                                    ->label('Quarterly Assessment (QA)')
                                    ->numeric()
                                    ->minValue(0)->maxValue(100)->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state) => ($this->shsQuarterlyAssessmentWeight = (int) $state))
                                    ->rules(['integer', 'min:0', 'max:100']),
                                Placeholder::make('total_weight')
                                    ->label('Total Weight')
                                    ->content(function (Get $get): HtmlString {
                                        $total = (int) $get('shsWrittenWorkWeight') + (int) $get('shsPerformanceTaskWeight') + (int) $get('shsQuarterlyAssessmentWeight');
                                        $color = $total === 100 ? 'text-success-600' : 'text-danger-600';
                                        return new HtmlString("<span class='text-lg font-bold {$color}'>{$total}%</span>");
                                    }),
                            ])
                            ->columns(4)
                            ->visible(fn (Get $get) => $get('gradingSystemType') === Team::GRADING_SYSTEM_SHS),

                        // --- General Display ---
                        Toggle::make('showFinalGrades')
                            ->label('Show Overall Grade Column')
                            ->default(true)
                            ->inline(false)
                            ->live()
                            ->afterStateUpdated(fn ($state) => ($this->showFinalGrades = (bool) $state)),
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
            $totalShsWeight = $this->shsWrittenWorkWeight + $this->shsPerformanceTaskWeight + $this->shsQuarterlyAssessmentWeight;
            if ($totalShsWeight !== 100) {
                Notification::make()->title('Cannot Save')->body('SHS weights must sum to 100%.')->danger()->send();
                return;
            }
            // Persist SHS weights if changed
            $this->team->shs_ww_weight = $this->shsWrittenWorkWeight;
            $this->team->shs_pt_weight = $this->shsPerformanceTaskWeight;
            $this->team->shs_qa_weight = $this->shsQuarterlyAssessmentWeight;
        } elseif ($this->team->usesCollegeTermGrading()) {
            // Validate Overall Term Weights
            $totalTermWeight = $this->collegePrelimWeight + $this->collegeMidtermWeight + $this->collegeFinalWeight;
            if ($totalTermWeight !== 100) {
                Notification::make()->title('Cannot Save')->body('College Overall Term weights must sum to 100%.')->danger()->send();
                return;
            }
            // Validate Term Component Weights (WW/PT within terms)
            $totalTermComponentWeight = $this->collegeTermWwWeight + $this->collegeTermPtWeight;
             if ($totalTermComponentWeight !== 100) {
                Notification::make()->title('Cannot Save')->body('College Term Component (WW/PT) weights must sum to 100%.')->danger()->send();
                return;
            }

            // Persist term weights if changed
            $this->team->college_prelim_weight = $this->collegePrelimWeight;
            $this->team->college_midterm_weight = $this->collegeMidtermWeight;
            $this->team->college_final_weight = $this->collegeFinalWeight;
            // Persist term component weights (New)
            $this->team->college_term_ww_weight = $this->collegeTermWwWeight;
            $this->team->college_term_pt_weight = $this->collegeTermPtWeight;
        }

        // Save team config changes if any occurred during validation checks
        if ($this->team->isDirty()) {
            $this->team->save();
        }

        // Proceed with saving scores
        DB::beginTransaction();
        try {
            $updatedCount = 0;
            $userId = Auth::id();
            $now = now();

            foreach ($this->activityScores as $studentId => $scores) {
                foreach ($scores as $activityId => $score) {
                    $activity = $this->activities->firstWhere('id', $activityId);
                    if (!$activity) continue;

                    $scoreValue = $score === '' || $score === null ? null : (float) $score;

                    // Validate score range
                    if ($scoreValue !== null && $activity->total_points !== null && ($scoreValue < 0 || $scoreValue > $activity->total_points)) {
                         throw ValidationException::withMessages([
                            "activityScores.{$studentId}.{$activityId}" => "Score must be between 0 and {$activity->total_points}."
                        ]);
                    }
                    
                    // Get existing submission or create new instance
                    $submission = ActivitySubmission::firstOrNew(
                        ['activity_id' => $activityId, 'student_id' => $studentId]
                    );

                    // Determine status and graded info
                    $newStatus = $submission->status ?? 'pending';
                    $newGradedBy = $submission->graded_by;
                    $newGradedAt = $submission->graded_at;

                    if ($scoreValue !== null && $submission->score !== $scoreValue) {
                        $newStatus = 'graded';
                        $newGradedBy = $userId;
                        $newGradedAt = $now;
                    } elseif ($scoreValue === null && $submission->score !== null) {
                         // Score removed, revert status? Or keep as graded but null score?
                         // Let's keep it 'graded' but allow null score to indicate removal
                         // $newStatus = 'pending'; // Optional: Revert status if score is cleared
                         // $newGradedBy = null;
                         // $newGradedAt = null;
                    }

                    // Update submission attributes
                    $submission->score = $scoreValue;
                    $submission->status = $newStatus;
                    $submission->graded_by = $newGradedBy;
                    $submission->graded_at = $newGradedAt;
                    $submission->final_grade = null; // Always clear final grade on score update

                    if ($submission->isDirty()) {
                       $submission->save();
                       $updatedCount++;
                    }
                }
            }

            DB::commit();
            Notification::make()->title('Grades Saved')->body("Successfully saved/updated {$updatedCount} scores.")->success()->send();
            $this->loadActivityScores(); // Reload scores and recalculate grades
        } catch (ValidationException $e) {
            DB::rollBack();
            Notification::make()->title('Validation Error')->body('Please correct score errors.')->danger()->send();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving scores: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            Notification::make()->title('Error')->body('An unexpected error occurred while saving scores.')->danger()->send();
        }
    }

    /**
     * @deprecated Use saveAllScores() instead
     */
    public function updateScore($studentId, $activityId): void
    {
        // Deprecated - logic moved to saveAllScores
        Notification::make()->title('Action Deprecated')->body('Please use the "Save All Scores" button.')->warning()->send();
    }

    // --- Update Team Settings ---
    protected function updateTeamGradingSystem(?string $systemType, ?string $collegeScale): void
    {
        if (!$this->team) return;

        $this->team->grading_system_type = $systemType;
        $this->gradingSystemType = $systemType;

        $isCollege = $systemType === Team::GRADING_SYSTEM_COLLEGE;
        $isTermBased = $isCollege && $collegeScale && in_array($collegeScale, Team::COLLEGE_TERM_SCALES);

        // Update College Scale
        $this->team->college_grading_scale = $isCollege ? $collegeScale : null;
        $this->collegeGradingScale = $isCollege ? $collegeScale : null;

        // Clear/Set SHS Weights
        $this->team->shs_ww_weight = !$isCollege ? $this->shsWrittenWorkWeight : null;
        $this->team->shs_pt_weight = !$isCollege ? $this->shsPerformanceTaskWeight : null;
        $this->team->shs_qa_weight = !$isCollege ? $this->shsQuarterlyAssessmentWeight : null;

        // Clear/Set College Overall Term Weights
        $this->team->college_prelim_weight = $isTermBased ? $this->collegePrelimWeight : null;
        $this->team->college_midterm_weight = $isTermBased ? $this->collegeMidtermWeight : null;
        $this->team->college_final_weight = $isTermBased ? $this->collegeFinalWeight : null;
        
        // Clear/Set College Term Component Weights (New)
        $this->team->college_term_ww_weight = $isTermBased ? $this->collegeTermWwWeight : null;
        $this->team->college_term_pt_weight = $isTermBased ? $this->collegeTermPtWeight : null;

        // Validate weights before saving
        $isValid = true;
        if (!$isCollege && ($this->shsWrittenWorkWeight + $this->shsPerformanceTaskWeight + $this->shsQuarterlyAssessmentWeight !== 100)) {
            Notification::make()->title('Invalid Weights')->body('SHS component weights must sum to 100%.')->danger()->send();
            $isValid = false;
        }
        if ($isTermBased && ($this->collegePrelimWeight + $this->collegeMidtermWeight + $this->collegeFinalWeight !== 100)) {
            Notification::make()->title('Invalid Weights')->body('College Overall Term weights must sum to 100%.')->danger()->send();
            $isValid = false;
        }
        if ($isTermBased && ($this->collegeTermWwWeight + $this->collegeTermPtWeight !== 100)) {
             Notification::make()->title('Invalid Weights')->body('College Term Component (WW/PT) weights must sum to 100%.')->danger()->send();
             $isValid = false;
         }

        if ($isValid && $this->team->isDirty()) {
            $this->team->save();
            Notification::make()->title('Settings Updated')->body('Grading system configuration saved.')->success()->send();
            $this->calculateAllStudentOverallGrades(); // Recalculate grades with new settings
        } elseif (!$this->team->isDirty()) {
             // If only system type/scale changed but weights were already correct
              $this->calculateAllStudentOverallGrades();
        }
    }

    /**
     * Calculate the overall grade value for a single student.
     */
    protected function calculateStudentOverallGradeValue(string $studentId): array
    {
        $studentScores = $this->activityScores[$studentId] ?? [];
        $defaultResult = ['final_grade' => null, 'term_grades' => null];

        if (empty($studentScores) || $this->activities->isEmpty() || !$this->gradingSystemType) {
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
                return ['final_grade' => $initialGrade, 'term_grades' => null];

            } elseif ($this->gradingSystemType === Team::GRADING_SYSTEM_COLLEGE) {
                $numericScale = $this->team->getCollegeNumericScale();

                if ($this->team->usesCollegeTermGrading()) {
                    // Pass the term component weights to the service method
                    return $this->gradingService->calculateCollegeFinalFinalGrade(
                        $studentScores,
                        $this->activities,
                        $this->collegePrelimWeight,
                        $this->collegeMidtermWeight,
                        $this->collegeFinalWeight,
                        $this->collegeTermWwWeight, // New
                        $this->collegeTermPtWeight, // New
                        $numericScale
                    );
                } elseif ($this->team->usesCollegeGwaGrading()) {
                    $gwa = $this->gradingService->calculateCollegeGwa(
                        $studentScores,
                        $this->activities,
                        $numericScale
                    );
                    return ['final_grade' => $gwa, 'term_grades' => null];
                } else {
                    return $defaultResult;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error calculating grade for student {$studentId}: " . $e->getMessage());
            return $defaultResult;
        }

        return $defaultResult;
    }

    /**
     * Get the formatted overall grade display string for a student.
     */
    public function getFormattedOverallGrade(string $studentId): HtmlString|string
    {
        $overallGradeValue = $this->studentOverallGrades[$studentId] ?? null;
        $numericScale = $this->team?->getCollegeNumericScale();

        if ($overallGradeValue === null) {
            return new HtmlString('<span class="text-gray-400 dark:text-gray-500">N/A</span>');
        }

        if ($this->gradingSystemType === Team::GRADING_SYSTEM_SHS) {
            $transmutedGrade = $this->gradingService->transmuteShsGrade($overallGradeValue);
            $descriptor = $this->gradingService->getShsGradeDescriptor($transmutedGrade);
            $color = $this->gradingService->getShsGradeColor($transmutedGrade);
            // Use existing SHS display logic (ensure it uses $transmutedGrade)
            return new HtmlString("<span class='{$color} font-bold text-lg' title='{$descriptor} ({$overallGradeValue}% Initial)'>{$transmutedGrade}</span>");
        } elseif ($this->gradingSystemType === Team::GRADING_SYSTEM_COLLEGE && $numericScale) {
            $formattedGrade = $this->gradingService->formatCollegeGrade($overallGradeValue, $numericScale);
            $color = $this->gradingService->getCollegeGradeColor($overallGradeValue, $numericScale);
            return new HtmlString("<span class='{$color} font-bold text-lg'>{$formattedGrade}</span>");
        }

        return new HtmlString('<span class="text-gray-400 dark:text-gray-500">N/A</span>');
    }

    public function calculateAllStudentOverallGrades(): void
    {
        if (!$this->students || !$this->activities || !$this->gradingSystemType) {
            $this->studentOverallGrades = [];
            $this->studentTermGrades = [];
            return;
        }

        $newOverallGrades = [];
        $newTermGrades = [];

        foreach ($this->students as $student) {
            $overallResult = $this->calculateStudentOverallGradeValue($student->id);
            $newOverallGrades[$student->id] = $overallResult['final_grade'];
            if (!empty($overallResult['term_grades'])) {
                $newTermGrades[$student->id] = $overallResult['term_grades'];
            }
        }
        $this->studentOverallGrades = $newOverallGrades;
        $this->studentTermGrades = $newTermGrades;

        $this->dispatch('gradesCalculated');
    }

     /**
     * @deprecated Category averages are not directly used in primary calculations anymore.
     */
    protected function calculateCategoryAverage(string $studentId, Collection $activities, string $category): string
    {
       // This might still be useful for display purposes but not core calculation
       // ... (keep existing logic or remove if truly unused)
       return 'N/A'; // Placeholder if removing
    }

    /**
     * @deprecated Final grade calculation is handled by calculateStudentOverallGradeValue.
     */
    protected function calculateFinalGrade(string $studentId, Collection $activities): string
    {
       return 'N/A'; // Deprecated
    }

     /**
     * @deprecated Final grade calculation is handled by calculateStudentOverallGradeValue.
     */
    protected function calculateFinalGradeValue(string $studentId, Collection $activities): ?float
    {
       return null; // Deprecated
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Grades')
                ->icon('heroicon-o-arrow-down-tray')
                ->size(ActionSize::Large)
                ->color('success')
                ->action(fn () => $this->exportGrades()),
        ];
    }

    // --- Export --- Adjusted for Term Grades
    protected function exportGrades()
    {
        if (!$this->team || $this->students->isEmpty() || $this->activities->isEmpty()) {
            return null;
        }

        $headers = ['Student Name'];
        $isShs = $this->team->usesShsGrading();
        $isCollegeTerm = $this->team->usesCollegeTermGrading();
        $isCollegeGwa = $this->team->usesCollegeGwaGrading();
        $numericScale = $this->team->getCollegeNumericScale();

        foreach ($this->activities as $activity) {
            $header = '';
            if ($isShs && $activity->component_type) {
                $header .= '[' . $activity->component_type_code . '] ';
            } elseif ($isCollegeTerm && $activity->term) {
                $header .= '[' . $activity->getTermCode() . '] ';
            }
            $header .= $activity->title;
            $header .= ' (' . ($activity->total_points ?? 'N/A') . ' pts';
            if ($isCollegeGwa && $activity->credit_units > 0) {
                $header .= ' / ' . $activity->credit_units . ' units';
            }
            $header .= ')';
            $headers[] = $header;
        }
        
        // Add Term Grade Headers if applicable
        if ($isCollegeTerm) {
            $headers[] = 'Prelim Grade';
            $headers[] = 'Midterm Grade';
            $headers[] = 'Final Term Grade';
        }

        if ($this->showFinalGrades) {
            $finalHeader = 'Overall Grade';
            if ($isShs) $finalHeader = 'Overall (Transmuted)';
            if ($isCollegeTerm) $finalHeader = 'Final Grade (Weighted Avg)';
            if ($isCollegeGwa) $finalHeader = 'GWA';
            $headers[] = $finalHeader;
        }

        $data = [];
        $data[] = $headers;

        foreach ($this->students as $student) {
            $row = [$student->name];
            $studentScores = $this->activityScores[$student->id] ?? [];

            foreach ($this->activities as $activity) {
                $row[] = $studentScores[$activity->id] ?? '';
            }
            
            // Add Term Grades if applicable
             if ($isCollegeTerm) {
                 $termGrades = $this->studentTermGrades[$student->id] ?? [];
                 $row[] = isset($termGrades[Activity::TERM_PRELIM]) ? $this->gradingService->formatCollegeGrade($termGrades[Activity::TERM_PRELIM], $numericScale, true) : '';
                 $row[] = isset($termGrades[Activity::TERM_MIDTERM]) ? $this->gradingService->formatCollegeGrade($termGrades[Activity::TERM_MIDTERM], $numericScale, true) : '';
                 $row[] = isset($termGrades[Activity::TERM_FINAL]) ? $this->gradingService->formatCollegeGrade($termGrades[Activity::TERM_FINAL], $numericScale, true) : '';
             }

            if ($this->showFinalGrades) {
                $overallGradeValue = $this->studentOverallGrades[$student->id] ?? null;
                $formattedOverall = 'N/A';
                if ($overallGradeValue !== null) {
                    if ($isShs) {
                        $formattedOverall = $this->gradingService->transmuteShsGrade($overallGradeValue);
                    } elseif (($isCollegeTerm || $isCollegeGwa) && $numericScale) {
                        $formattedOverall = $this->gradingService->formatCollegeGrade($overallGradeValue, $numericScale, true); // Raw format for export
                    } else {
                        $formattedOverall = number_format($overallGradeValue, 2); // Fallback
                    }
                }
                $row[] = $formattedOverall;
            }
            $data[] = $row;
        }

        // --- CSV generation logic --- (Simplified for brevity)
        $callback = function () use ($data): void {
            $file = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };
        $filename = Str::slug($this->team->name . ' Gradesheet ' . now()->format('Y-m-d')) . '.csv';

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function openFinalGradeModal(string $studentId): void
    {
        $this->calculateAllStudentOverallGrades();
        $this->dispatch('open-modal', id: 'final-grade-modal', studentId: $studentId);
    }

    public function getStudentGradeBreakdown(string $studentId): array
    {
        $student = $this->students->firstWhere('id', $studentId);
        if (!$student) return ['error' => 'Student not found'];

        $studentScores = $this->activityScores[$studentId] ?? [];
        $overallGradeValue = $this->studentOverallGrades[$studentId] ?? null;
        $termGrades = $this->studentTermGrades[$studentId] ?? null; // Get pre-calculated term grades
        $numericScale = $this->team?->getCollegeNumericScale();

        $breakdown = [];
        $activitiesData = [];

        // Prepare Detailed Activity Data (Mostly unchanged)
        foreach ($this->activities as $activity) {
            $score = $studentScores[$activity->id] ?? null;
            $percentage = null;
            if ($score !== null && $activity->total_points > 0) {
                $percentage = round(((float) $score / (float) $activity->total_points) * 100, 2);
            }
            $activityFormattedPercentage = $percentage !== null ? number_format($percentage, 1) . '%' : '-';
            $activityColor = 'text-gray-500 dark:text-gray-400';
            if ($percentage !== null) {
                 if ($numericScale && $this->team->usesCollegeGrading()) {
                    $tempScaleGrade = $this->gradingService->convertPercentageToCollegeScale($percentage, $numericScale);
                    $activityColor = $this->gradingService->getCollegeGradeColor($tempScaleGrade, $numericScale);
                 } elseif ($this->team->usesShsGrading()) {
                     // SHS Percentage Coloring
                     if ($percentage >= 90) $activityColor = 'text-success-600 dark:text-success-400';
                     elseif ($percentage >= 75) $activityColor = 'text-warning-600 dark:text-warning-400';
                     else $activityColor = 'text-danger-600 dark:text-danger-400';
                 }
            }

            $activitiesData[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'category' => $activity->category, // Include category
                'component_type' => $activity->component_type,
                'component_code' => $activity->component_type_code,
                'term' => $activity->term,
                'term_code' => $activity->getTermCode(),
                'total_points' => $activity->total_points,
                'credit_units' => $activity->credit_units,
                'score' => $score,
                'percentage' => $percentage,
                'formatted_percentage' => $activityFormattedPercentage,
                'color_class' => $activityColor,
            ];
        }

        // --- Generate Breakdown based on Grading System ---
        if ($this->team->usesShsGrading()) {
            // ... (Keep existing SHS breakdown logic) ...
             $initialGrade = $overallGradeValue;
             $transmutedGrade = $initialGrade !== null ? $this->gradingService->transmuteShsGrade($initialGrade) : null;
             $descriptor = $transmutedGrade !== null ? $this->gradingService->getShsGradeDescriptor($transmutedGrade) : 'N/A';
             $colorClass = $transmutedGrade !== null ? $this->gradingService->getShsGradeColor($transmutedGrade) : 'text-gray-400';
 
             $wwDetails = $this->gradingService->calculateShsComponentDetails($studentScores, $this->activities, Activity::COMPONENT_WRITTEN_WORK);
             $ptDetails = $this->gradingService->calculateShsComponentDetails($studentScores, $this->activities, Activity::COMPONENT_PERFORMANCE_TASK);
             $qaDetails = $this->gradingService->calculateShsComponentDetails($studentScores, $this->activities, Activity::COMPONENT_QUARTERLY_ASSESSMENT);
 
             $formatComponent = function ($details, $weight) {
                 $ps = $details['percentage_score'];
                 $details['formatted_ps'] = $ps !== null ? number_format($ps, 2) . '%' : 'N/A';
                 $details['formatted_ws'] = $ps !== null ? number_format($ps * ($weight / 100), 2) : 'N/A';
                 $details['formatted_raw'] = $details['total_raw_score'] !== null ? $details['total_raw_score'] . ' / ' . $details['total_highest_possible_score'] : 'N/A';
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
                     'ww' => $formatComponent($wwDetails, $this->shsWrittenWorkWeight),
                     'pt' => $formatComponent($ptDetails, $this->shsPerformanceTaskWeight),
                     'qa' => $formatComponent($qaDetails, $this->shsQuarterlyAssessmentWeight),
                 ],
                 'initial_grade' => $initialGrade,
                 'formatted_initial_grade' => $initialGrade !== null ? number_format($initialGrade, 2) : 'N/A',
                 'transmuted_grade' => $transmutedGrade,
                 'formatted_transmuted_grade' => $transmutedGrade ?? 'N/A',
                 'descriptor' => $descriptor,
                 'color_class' => $colorClass,
             ];

        } elseif ($this->team->usesCollegeTermGrading() && $numericScale) {
            // --- College Term Breakdown --- 
            $formattedTermGrades = [];
            $termGradeColors = [];
            $termComponentDetails = []; // To store WW/PT breakdown within each term

            foreach ([Activity::TERM_PRELIM, Activity::TERM_MIDTERM, Activity::TERM_FINAL] as $termKey) {
                $grade = $termGrades[$termKey] ?? null;
                $formattedTermGrades[$termKey] = $grade !== null ? $this->gradingService->formatCollegeGrade($grade, $numericScale) : 'N/A';
                $termGradeColors[$termKey] = $grade !== null ? $this->gradingService->getCollegeGradeColor($grade, $numericScale) : 'text-gray-400';
                
                // Calculate WW/PT details for this term
                $termActivities = collect($activitiesData)->where('term', $termKey); // Use pre-filtered activity data
                $termWwTotalPercent = 0; $termWwCount = 0;
                $termPtTotalPercent = 0; $termPtCount = 0;
                
                foreach ($termActivities as $act) {
                    if ($act['percentage'] !== null) {
                        // Use string comparison for category
                        if ($act['category'] === 'written') {
                            $termWwTotalPercent += $act['percentage'];
                            $termWwCount++;
                        } elseif ($act['category'] === 'performance') {
                             $termPtTotalPercent += $act['percentage'];
                             $termPtCount++;
                        }
                    } // <-- Add missing closing brace here
                }
                $termWwAvg = $termWwCount > 0 ? round($termWwTotalPercent / $termWwCount, 2) : null;
                $termPtAvg = $termPtCount > 0 ? round($termPtTotalPercent / $termPtCount, 2) : null;
                
                $termComponentDetails[$termKey] = [
                   'ww_avg' => $termWwAvg,
                   'ww_formatted_avg' => $termWwAvg !== null ? number_format($termWwAvg, 2) . '%' : 'N/A',
                   'pt_avg' => $termPtAvg,
                   'pt_formatted_avg' => $termPtAvg !== null ? number_format($termPtAvg, 2) . '%' : 'N/A',
                   'ww_weighted_part' => ($termWwAvg !== null && $this->collegeTermWwWeight !== null) ? round($termWwAvg * ($this->collegeTermWwWeight / 100), 2) : null,
                   'pt_weighted_part' => ($termPtAvg !== null && $this->collegeTermPtWeight !== null) ? round($termPtAvg * ($this->collegeTermPtWeight / 100), 2) : null,
                ];
            }

            $formattedFinalGrade = $overallGradeValue !== null ? $this->gradingService->formatCollegeGrade($overallGradeValue, $numericScale) : 'N/A';
            $finalGradeColor = $overallGradeValue !== null ? $this->gradingService->getCollegeGradeColor($overallGradeValue, $numericScale) : 'text-gray-400';

            $breakdown = [
                'type' => 'college_term',
                'scale' => $numericScale,
                'scale_description' => $this->team->grading_system_description,
                'term_weights' => [
                    'prelim' => $this->collegePrelimWeight,
                    'midterm' => $this->collegeMidtermWeight,
                    'final' => $this->collegeFinalWeight,
                ],
                'component_weights' => [ // New: Pass term component weights
                    'ww' => $this->collegeTermWwWeight,
                    'pt' => $this->collegeTermPtWeight,
                ],
                'term_grades' => $termGrades,
                'formatted_term_grades' => $formattedTermGrades,
                'term_grade_colors' => $termGradeColors,
                'term_component_details' => $termComponentDetails, // New: Pass term component details
                'final_grade' => $overallGradeValue,
                'formatted_final_grade' => $formattedFinalGrade,
                'final_grade_color' => $finalGradeColor,
            ];

        } elseif ($this->team->usesCollegeGwaGrading() && $numericScale) {
            // ... (Keep existing GWA breakdown logic - it doesn't use term component weights) ...
             $collegeGradeDetails = $this->gradingService->calculateCollegeGwaDetails($studentScores, $this->activities, $numericScale);
             $formattedGwa = $overallGradeValue !== null ? $this->gradingService->formatCollegeGrade($overallGradeValue, $numericScale) : 'N/A';
             $gwaColor = $overallGradeValue !== null ? $this->gradingService->getCollegeGradeColor($overallGradeValue, $numericScale) : 'text-gray-400';
 
             $formattedActivityGrades = [];
             foreach ($collegeGradeDetails['activity_grades'] ?? [] as $actId => $gradeInfo) {
                 $formattedActivityGrades[$actId] = $gradeInfo;
                 $formattedActivityGrades[$actId]['formatted_scale_grade'] = $this->gradingService->formatCollegeGrade($gradeInfo['scale_grade'], $numericScale);
                 $formattedActivityGrades[$actId]['color_class'] = $this->gradingService->getCollegeGradeColor($gradeInfo['scale_grade'], $numericScale);
                 $formattedActivityGrades[$actId]['formatted_weighted_part'] = number_format($gradeInfo['scale_grade'] * $gradeInfo['units'], 2);
             }
 
             $breakdown = [
                 'type' => 'college_gwa',
                 'scale' => $numericScale,
                 'scale_description' => $this->team->grading_system_description,
                 'gwa' => $overallGradeValue,
                 'formatted_gwa' => $formattedGwa,
                 'gwa_color' => $gwaColor,
                 'total_units' => $collegeGradeDetails['total_units'] ?? 0,
                 'formatted_total_units' => number_format($collegeGradeDetails['total_units'] ?? 0, 2),
                 'weighted_grade_sum' => $collegeGradeDetails['weighted_grade_sum'] ?? 0,
                 'formatted_weighted_grade_sum' => number_format($collegeGradeDetails['weighted_grade_sum'] ?? 0, 2),
                 'activity_grades' => $formattedActivityGrades,
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
     * @deprecated Use getStudentGradeBreakdown instead.
     */
    public function getStudentScores(string $studentId): array
    {
        // Deprecated - kept for potential compatibility, but breakdown is preferred
        $student = Student::findOrFail($studentId);
        $activities = Activity::where('team_id', $this->teamId)->orderBy('term')->orderBy('component_type')->orderBy('created_at')->get();
        $scores = $this->activityScores[$studentId] ?? [];
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

        if ($termGradeValue === null || !$numericScale) {
            return new HtmlString('<span class="text-gray-400 dark:text-gray-500">-</span>');
        }

        $formattedGrade = $this->gradingService->formatCollegeGrade($termGradeValue, $numericScale);
        $color = $this->gradingService->getCollegeGradeColor($termGradeValue, $numericScale);

        $rawValue = number_format($termGradeValue, 2);
        $title = ucfirst($termKey) . " Grade: {$rawValue}"; // Use raw value for tooltip

        return new HtmlString("<span class='{$color} font-semibold' title='{$title}'>{$formattedGrade}</span>");
    }

    // Helper to get term background color for table headers
    public function getTermHeaderClass(string $term): string
    {
        return match ($term) {
            Activity::TERM_PRELIM => 'bg-teal-50 dark:bg-teal-900/50',
            Activity::TERM_MIDTERM => 'bg-purple-50 dark:bg-purple-900/50',
            Activity::TERM_FINAL => 'bg-orange-50 dark:bg-orange-900/50',
            default => 'bg-gray-50 dark:bg-white/5',
        };
    }

    // Helper to get SHS component background color for table headers
    public function getShsComponentHeaderClass(string $componentType): string
    {
        return match ($componentType) {
            Activity::COMPONENT_WRITTEN_WORK => 'bg-blue-50 dark:bg-blue-900/50',
            Activity::COMPONENT_PERFORMANCE_TASK => 'bg-red-50 dark:bg-red-900/50',
            Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'bg-yellow-50 dark:bg-yellow-900/50',
            default => 'bg-gray-50 dark:bg-white/5',
        };
    }
}
