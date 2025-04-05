<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Resources\ActivityResource; // For helpers
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Team; // Added Team model
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Import DB facade
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

// Make sure Activity model uses HasUuids if your IDs are UUIDs
// use Illuminate\Database\Eloquent\Concerns\HasUuids; // If needed

class ActivityCreator extends Component implements HasForms
{
    use InteractsWithForms;

    // State Management
    public int $currentStep = 1; // 1: Template Selection, 2: Details, 3: Config, 4: Submission, 5: Review

    public bool $isSubmitting = false; // Prevent double clicks

    public bool $previewMode = false; // Toggle between edit and preview modes

    public bool $showHelpTips = true; // Show/hide contextual help

    public array $stepProgress = [1 => true, 2 => false, 3 => false, 4 => false, 5 => false]; // Track completed steps

    // Activity Properties (camelCase for Livewire, map to snake_case on save)
    public ?string $selectedTemplateKey = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $instructions = null;

    public ?string $activityTypeId = null;

    public string $status = 'draft';

    public ?string $dueDate = null; // Renamed from deadline

    public string $mode = 'individual';

    public string $category = 'written';

    public ?float $totalPoints = 10.0; // Changed to float to match model cast potentially

    public ?string $format = null; // Quiz, Assignment, etc.

    public ?string $customFormat = null;

    public ?int $groupCount = null;

    public array $roles = []; // Structure: [['name' => '', 'description' => '']]

    public string $submissionType = 'resource';

    public bool $allowFileUploads = true;

    public bool $allowTextEntry = false;

    public array $allowedFileTypes = [];

    public ?int $maxFileSize = 10; // MB

    public array $formStructure = []; // Builder structure, align name with model ($casts -> form_config?)

    public bool $allowTeacherSubmission = false;

    // --- NEW Properties for Grading System ---
    public ?string $componentType = null; // SHS: written_work, performance_task, quarterly_assessment

    public ?string $term = null; // College: prelim, midterm, final

    public ?float $creditUnits = null; // College: GWA units

    // Visual Builder Options
    public array $activeQuestion = []; // Current question being edited in form builder

    public string $currentPanel = 'edit'; // edit, preview, or help

    public array $visualElements = []; // Visual elements for drag-and-drop functionality

    public array $colorThemes = [
        'default' => ['bg' => 'bg-white dark:bg-gray-800', 'accent' => 'primary'],
        'blue' => ['bg' => 'bg-blue-50 dark:bg-blue-900', 'accent' => 'blue'],
        'green' => ['bg' => 'bg-green-50 dark:bg-green-900', 'accent' => 'green'],
        'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900', 'accent' => 'purple'],
        'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-900', 'accent' => 'amber'],
    ];

    public string $selectedTheme = 'default';

    // Data for Selects
    public array $activityTypes = [];

    public ?Team $currentTeam = null; // Store current team info

    // Template Definitions
    protected array $templates = [];

    // Listeners for potential external components (like a modal form builder)
    protected $listeners = [
        'updateFormStructure',
        'togglePreviewMode',
        'addVisualElement',
        'removeVisualElement',
        'updateVisualElement',
    ];

    public function mount(): void
    {
        $this->currentTeam = Auth::user()?->currentTeam; // Get current team
        if (! $this->currentTeam) {
            // Handle error: No team context
            Notification::make()->title('Error')->body('Cannot create activity without an active team.')->danger()->send();

            // Potentially redirect or disable component
            return;
        }

        $this->activityTypes = ActivityType::query()
            // ->where('team_id', $this->currentTeam->id) // Filter by team if types are team-specific
            // ->orWhereNull('team_id')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->templates = $this->defineTemplates();
        $this->visualElements = $this->defineVisualElements();
        $this->applyTemplateDefaults('blank');
    }

    protected function defineTemplates(): array
    {
        // Add component_type or term defaults based on template logic and team type (if known here)
        // Example: A quiz might be 'written_work' for SHS, or have no term default for College.
        // For simplicity, we'll set defaults and let the save() method filter based on actual team setting.
        return [
            'quiz' => [
                'name' => 'Quiz',
                'description' => 'Assess understanding (e.g., multiple choice, short answer).',
                'icon' => 'heroicon-o-question-mark-circle',
                'thumbnail' => 'quiz-template.jpg',
                'defaults' => [
                    'mode' => 'individual', 'category' => 'written', 'format' => 'quiz',
                    'submissionType' => 'form', 'title' => 'Quiz: ', 'totalPoints' => 10.0,
                    'allowFileUploads' => false, 'allowTextEntry' => false,
                    'description' => '<p>Short assessment on [Topic].</p>',
                    'formStructure' => [
                        ['type' => 'multiple_choice', 'question' => 'Sample Question 1?', 'options' => ['Option A', 'Option B', 'Option C']],
                        ['type' => 'short_answer', 'question' => 'Sample Question 2?', 'max_length' => 250],
                    ],
                    'componentType' => Activity::COMPONENT_WRITTEN_WORK, // Default for SHS
                    'term' => null, // No default term for College
                    'creditUnits' => 1.0, // Default units for College
                ],
            ],
            'assignment' => [
                'name' => 'Assignment / Homework',
                'description' => 'Students submit work (e.g., essays, problem sets, files).',
                'icon' => 'heroicon-o-document-text',
                'thumbnail' => 'assignment-template.jpg',
                'defaults' => [
                    'mode' => 'individual', 'category' => 'written', 'format' => 'assignment',
                    'submissionType' => 'resource', 'title' => 'Assignment: ', 'totalPoints' => 20.0,
                    'allowFileUploads' => true, 'allowTextEntry' => true,
                    'description' => '<p>Complete the following task(s) based on [Topic/Lesson].</p>',
                    'instructions' => '<p>1. Review materials.</p><p>2. Complete work.</p><p>3. Submit file(s)/text.</p>',
                    'componentType' => Activity::COMPONENT_WRITTEN_WORK, // Default for SHS
                    'term' => null, // No default term for College
                    'creditUnits' => 3.0, // Default units for College
                ],
            ],
            'project' => [
                'name' => 'Project',
                'description' => 'Larger scope work, often collaborative.',
                'icon' => 'heroicon-o-cube-transparent',
                'thumbnail' => 'project-template.jpg',
                'defaults' => [
                    'mode' => 'group', 'category' => 'performance', 'format' => 'project',
                    'submissionType' => 'resource', 'title' => 'Project: ', 'totalPoints' => 100.0,
                    'allowFileUploads' => true, 'allowTextEntry' => false, 'groupCount' => 4,
                    'description' => '<p>Collaborative project on [Theme/Subject].</p>',
                    'instructions' => '<p>Work with your group to create [Deliverable].</p>',
                    'roles' => [
                        ['name' => 'Coordinator', 'description' => 'Organizes tasks.'],
                        ['name' => 'Researcher', 'description' => 'Gathers info.'],
                        ['name' => 'Presenter/Writer', 'description' => 'Compiles/presents work.'],
                    ],
                    'componentType' => Activity::COMPONENT_PERFORMANCE_TASK, // Default for SHS
                    'term' => Activity::TERM_FINAL, // Default term for College Project
                    'creditUnits' => 5.0, // Default units for College
                ],
            ],
            'reporting' => [
                'name' => 'Reporting / Presentation',
                'description' => 'Students present findings, often graded manually.',
                'icon' => 'heroicon-o-presentation-chart-bar',
                'thumbnail' => 'reporting-template.jpg',
                'defaults' => [
                    'mode' => 'group', 'category' => 'performance', 'format' => 'reporting',
                    'submissionType' => 'manual', 'title' => 'Report/Presentation: ', 'totalPoints' => 50.0,
                    'allowFileUploads' => false, 'allowTextEntry' => false,
                    'description' => '<p>Present findings on [Topic].</p>',
                    'instructions' => '<p>Prepare presentation covering [Key Points].</p>',
                    'componentType' => Activity::COMPONENT_PERFORMANCE_TASK, // Default for SHS
                    'term' => Activity::TERM_MIDTERM, // Default term for College Reporting
                    'creditUnits' => 2.0, // Default units for College
                ],
            ],
            'discussion' => [
                'name' => 'Discussion / Participation',
                'description' => 'Graded participation in class or online forums.',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'thumbnail' => 'discussion-template.jpg',
                'defaults' => [
                    'mode' => 'individual', 'category' => 'performance', 'format' => 'discussion',
                    'submissionType' => 'manual', 'title' => 'Discussion: ', 'totalPoints' => 10.0,
                    'allowFileUploads' => false, 'allowTextEntry' => false,
                    'description' => '<p>Participate actively in the discussion about [Topic].</p>',
                    'instructions' => '<p>Come prepared to share thoughts based on [Prompts].</p>',
                    'componentType' => Activity::COMPONENT_PERFORMANCE_TASK, // Default for SHS
                    'term' => null, // No default term
                    'creditUnits' => 0.5, // Default units for College
                ],
            ],
            'blank' => [
                'name' => 'Start from Scratch',
                'description' => 'Build your activity with no pre-filled settings.',
                'icon' => 'heroicon-o-document-plus',
                'thumbnail' => 'blank-template.jpg',
                'defaults' => [
                    'title' => '', 'description' => '', 'instructions' => '', 'activityTypeId' => null,
                    'status' => 'draft', 'dueDate' => null, 'mode' => 'individual', 'category' => 'written',
                    'totalPoints' => 10.0, 'format' => null, 'customFormat' => null, 'groupCount' => null,
                    'roles' => [], 'submissionType' => 'resource', 'allowFileUploads' => true, 'allowTextEntry' => false,
                    'allowedFileTypes' => [], 'maxFileSize' => 10, 'formStructure' => [], 'allowTeacherSubmission' => false,
                    // --- Blank defaults for new fields ---
                    'componentType' => null,
                    'term' => null,
                    'creditUnits' => null,
                ],
            ],
        ];
    }

    protected function defineVisualElements(): array
    {
        return [
            'form_elements' => [
                [
                    'type' => 'multiple_choice',
                    'name' => 'Multiple Choice',
                    'icon' => 'heroicon-o-check-circle',
                    'description' => 'Create a question with multiple choice options',
                ],
                [
                    'type' => 'short_answer',
                    'name' => 'Short Answer',
                    'icon' => 'heroicon-o-chat-bubble-bottom-center-text',
                    'description' => 'Create a question with a text field for short responses',
                ],
                [
                    'type' => 'essay',
                    'name' => 'Essay/Long Answer',
                    'icon' => 'heroicon-o-document-text',
                    'description' => 'Create a question with a large text area for longer responses',
                ],
                [
                    'type' => 'file_upload',
                    'name' => 'File Upload',
                    'icon' => 'heroicon-o-paper-clip',
                    'description' => 'Add a file upload field',
                ],
                [
                    'type' => 'section_break',
                    'name' => 'Section Break',
                    'icon' => 'heroicon-o-bars-3',
                    'description' => 'Add a section divider with optional title and description',
                ],
            ],
            'content_elements' => [
                [
                    'type' => 'rich_text',
                    'name' => 'Rich Text',
                    'icon' => 'heroicon-o-document-text',
                    'description' => 'Add formatted text, images, and links',
                ],
                [
                    'type' => 'image',
                    'name' => 'Image',
                    'icon' => 'heroicon-o-photo',
                    'description' => 'Add an image with optional caption',
                ],
                [
                    'type' => 'video',
                    'name' => 'Video',
                    'icon' => 'heroicon-o-play',
                    'description' => 'Embed a video from YouTube, Vimeo, etc.',
                ],
                [
                    'type' => 'document',
                    'name' => 'Document',
                    'icon' => 'heroicon-o-document',
                    'description' => 'Embed a document or provide as a download',
                ],
            ],
        ];
    }

    public function selectTemplate(string $key): void
    {
        if (! isset($this->templates[$key])) {
            Notification::make()->title('Error')->body('Selected template not found.')->danger()->send();

            return;
        }
        $this->selectedTemplateKey = $key;
        $this->applyTemplateDefaults($key);
        $this->updateStepProgress(1, true);
        $this->currentStep = 2; // Move to details step
    }

    protected function applyTemplateDefaults(string $key): void
    {
        // Use 'blank' as the baseline reset
        $baseline = $this->defineTemplates()['blank']['defaults'];
        foreach ($baseline as $prop => $value) {
            $camelCaseProp = Str::camel($prop);
            if (property_exists($this, $camelCaseProp)) {
                $this->$camelCaseProp = $value;
            }
        }

        // Apply selected template's defaults only if not 'blank'
        if ($key !== 'blank' && isset($this->templates[$key])) {
            $defaults = $this->templates[$key]['defaults'] ?? [];
            foreach ($defaults as $prop => $value) {
                $camelCaseProp = Str::camel($prop);
                if (property_exists($this, $camelCaseProp)) {
                    $this->$camelCaseProp = $value;
                }
            }
            // Explicitly set arrays/complex types from defaults
            $this->roles = $defaults['roles'] ?? [];
            $this->formStructure = $defaults['formStructure'] ?? [];
            // Apply new fields from defaults
            $this->componentType = $defaults['componentType'] ?? null;
            $this->term = $defaults['term'] ?? null;
            $this->creditUnits = $defaults['creditUnits'] ?? null;
        }

        $this->resetValidation(); // Clear errors on template change
    }

    public function nextStep(): void
    {
        // Validate current step before proceeding
        if ($this->validateStep($this->currentStep)) {
            if ($this->currentStep < 5) { // 5 is Review step
                $this->updateStepProgress($this->currentStep, true);
                $this->currentStep++;
            }
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) { // Cannot go back from template selection
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step === $this->currentStep) {
            return;
        } // Do nothing if clicking current step

        if ($step < $this->currentStep) {
            // Allow jumping back freely
            $this->currentStep = $step;
        } else {
            // Validate all steps *up to* the target step before jumping forward
            for ($i = 2; $i < $step; $i++) { // Start validation from step 2
                if (! $this->validateStep($i, false)) { // Don't show notification on intermediate checks
                    $this->currentStep = $i; // Stop at the step that failed validation
                    Notification::make()
                        ->title('Incomplete Step')
                        ->body("Please complete step {$i} before proceeding.")
                        ->warning()
                        ->send();

                    return; // Prevent jumping forward
                }
            }
            // If all previous steps are valid, jump to the target step
            $this->currentStep = $step;
        }
    }

    protected function updateStepProgress(int $step, bool $completed): void
    {
        if (isset($this->stepProgress[$step])) {
            $this->stepProgress[$step] = $completed;
        }
    }

    public function togglePreviewMode(): void
    {
        $this->previewMode = ! $this->previewMode;
    }

    public function toggleHelpTips(): void
    {
        $this->showHelpTips = ! $this->showHelpTips;
    }

    public function setTheme(string $theme): void
    {
        if (isset($this->colorThemes[$theme])) {
            $this->selectedTheme = $theme;
        }
    }

    public function addVisualElement(array $element): void
    {
        if ($this->submissionType === 'form') {
            $this->formStructure[] = $element;
            $this->updateStepProgress(4, ! empty($this->formStructure));
        }
    }

    public function removeVisualElement(int $index): void
    {
        if (isset($this->formStructure[$index])) {
            unset($this->formStructure[$index]);
            $this->formStructure = array_values($this->formStructure);
            $this->updateStepProgress(4, ! empty($this->formStructure));
        }
    }

    public function updateVisualElement(int $index, array $data): void
    {
        if (isset($this->formStructure[$index])) {
            $this->formStructure[$index] = array_merge($this->formStructure[$index], $data);
        }
    }

    public function updateFormStructure(array $structure): void
    {
        $this->formStructure = $structure;
        $this->updateStepProgress(4, ! empty($this->formStructure));
    }

    public function editQuestion(int $index): void
    {
        if (isset($this->formStructure[$index])) {
            $this->activeQuestion = $this->formStructure[$index];
            $this->activeQuestion['index'] = $index;
            $this->dispatch('open-question-editor');
        }
    }

    public function saveActiveQuestion(): void
    {
        if (isset($this->activeQuestion['index'])) {
            $index = $this->activeQuestion['index'];
            unset($this->activeQuestion['index']);
            $this->updateVisualElement($index, $this->activeQuestion);
            $this->activeQuestion = [];
        }
    }

    // Centralized validation rules getter
    protected function getValidationRules(int $step): array
    {
        // Fetch team settings for conditional rules
        $isCollege = $this->currentTeam?->usesCollegeGrading() ?? false;
        $isShs = $this->currentTeam?->usesShsGrading() ?? false;
        $isCollegeTerm = $this->currentTeam?->usesCollegeTermGrading() ?? false;
        $isCollegeGwa = $this->currentTeam?->usesCollegeGwaGrading() ?? false;

        $rules = [
            2 => [ // Step 2: Details
                'title' => ['required', 'string', 'max:255'],
                'activityTypeId' => ['required', Rule::exists('activity_types', 'id')],
                'status' => ['required', Rule::in(['draft', 'published'])],
                'description' => ['nullable', 'string'],
                'instructions' => ['nullable', 'string'],
                'dueDate' => ['nullable', 'date', 'after_or_equal:today'], // Renamed from deadline
            ],
            3 => [ // Step 3: Configuration
                'mode' => ['required', Rule::in(['individual', 'group', 'take_home'])],
                'totalPoints' => ['required', 'numeric', 'min:0'],
                'format' => ['required', 'string', 'max:100'],
                'customFormat' => ['required_if:format,other', 'nullable', 'string', 'max:100'],
                'groupCount' => ['required_if:mode,group', 'nullable', 'integer', 'min:2', 'max:100'],
                'roles' => ['nullable', 'array', Rule::requiredIf($this->mode === 'group')],
                'roles.*.name' => ['required_with:roles', 'string', 'max:100'],
                'roles.*.description' => ['nullable', 'string', 'max:500'],

                // --- Conditional Validation ---
                'componentType' => [Rule::requiredIf($isShs), 'nullable', Rule::in(array_keys(ActivityResource::getShsComponentOptions()))],
                'term' => [Rule::requiredIf($isCollegeTerm), 'nullable', Rule::in(array_keys(ActivityResource::getCollegeTermOptions()))],
                'creditUnits' => [Rule::requiredIf($isCollegeGwa), 'nullable', 'numeric', 'min:0', 'max:100'], // Added max
            ],
            4 => [ // Step 4: Submission
                'submissionType' => ['required', Rule::in(['resource', 'form', 'manual'])],
                // --- Resource Specific ---
                'allowFileUploads' => ['required_if:submissionType,resource', 'boolean'],
                'allowTextEntry' => ['required_if:submissionType,resource', 'boolean'],
                // Custom rule: Ensure at least one is true if submission type is 'resource'
                'allowFileUploads' => ['required_if:submissionType,resource', 'boolean', function ($attribute, $value, $fail) {
                    if ($this->submissionType === 'resource' && ! $this->allowFileUploads && ! $this->allowTextEntry) {
                        $fail('If submission type is "File Upload / Text Entry", you must allow at least one of file uploads or text entry.');
                    }
                }],
                'allowedFileTypes' => ['nullable', 'array', Rule::requiredIf($this->submissionType === 'resource' && $this->allowFileUploads)],
                'maxFileSize' => ['nullable', 'integer', 'min:1', 'max:100', Rule::requiredIf($this->submissionType === 'resource' && $this->allowFileUploads)], // Example max 100MB
                // --- Form Specific ---
                // Need more specific validation based on builder structure?
                'formStructure' => ['array', Rule::requiredIf($this->submissionType === 'form')],
                // --- General ---
                'allowTeacherSubmission' => ['boolean'], // Allow teacher submission shouldn't prevent saving
            ],
        ];

        return $rules[$step] ?? [];
    }

    // Validation logic
    protected function validateStep(int $step, bool $notify = true): bool
    {
        try {
            $rules = $this->getValidationRules($step);
            if (! empty($rules)) {
                // Validate only the properties relevant to the current step
                $this->validate($rules);
            }

            return true; // Passed validation
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($notify) {
                Notification::make()
                    ->title('Check Your Input')
                    ->body("There are errors in step {$step}. Please review the fields.")
                    ->danger()
                    ->send();
            }

            // Optionally, dispatch browser event to focus on first error
            // $this->dispatchBrowserEvent('validation-failed', ['step' => $step]);
            return false; // Failed validation
        }
    }

    // Final Save action
    public function save(): void
    {
        if ($this->isSubmitting) {
            return;
        }
        $this->isSubmitting = true;

        // Fetch fresh team settings before validation/saving
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            Notification::make()->title('Error')->body('Cannot save activity: Team context lost.')->danger()->send();
            $this->isSubmitting = false;

            return;
        }
        $isCollege = $team->usesCollegeGrading();
        $isShs = $team->usesShsGrading();
        $isCollegeTerm = $team->usesCollegeTermGrading();
        $isCollegeGwa = $team->usesCollegeGwaGrading();

        // Final validation across all relevant steps
        $allRules = array_merge(
            $this->getValidationRules(2),
            $this->getValidationRules(3),
            $this->getValidationRules(4)
        );

        try {
            // Manually inject team settings into validation context if needed,
            // or ensure getValidationRules uses $this->currentTeam correctly.
            // $this->validate($allRules); // Assuming getValidationRules uses $this->currentTeam correctly
            $validatedData = $this->validate($allRules); // Validate and get validated data
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ... existing error handling ...
            $this->isSubmitting = false;

            return;
        }

        // Prepare data for model creation
        $activityData = [
            'team_id' => $team->id,
            'teacher_id' => Auth::id(),
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'activity_type_id' => $this->activityTypeId,
            'status' => $this->status,
            'due_date' => $this->dueDate, // Use correct property name
            'mode' => $this->mode,
            'total_points' => (float) $this->totalPoints,
            'format' => $this->format === 'other' ? $this->customFormat : $this->format,
            'submission_type' => $this->submissionType,
            'allow_teacher_submission' => $this->allowTeacherSubmission,
            'allow_file_uploads' => $this->submissionType === 'resource' ? $this->allowFileUploads : false,
            'allow_text_entry' => $this->submissionType === 'resource' ? $this->allowTextEntry : false,
            'allowed_file_types' => ($this->submissionType === 'resource' && $this->allowFileUploads) ? $this->allowedFileTypes : [],
            'max_file_size' => ($this->submissionType === 'resource' && $this->allowFileUploads) ? $this->maxFileSize : null,
            'form_config' => $this->submissionType === 'form' ? $this->formStructure : [],

            // --- Conditionally add term/component/units ---
            'component_type' => $isShs ? $this->componentType : null,
            'term' => $isCollegeTerm ? $this->term : null, // Only save term if College Term system
            'credit_units' => $isCollege ? $this->creditUnits : null, // Only save units if College system
        ];

        // Prepare roles only if mode is group
        $activityRoles = ($this->mode === 'group' && ! empty($this->roles)) ? $this->roles : [];

        try {
            DB::beginTransaction();

            $activity = Activity::create($activityData);

            // Save roles if it's a group activity and roles are defined
            if ($activity->mode === 'group' && ! empty($activityRoles)) {
                // ... existing role saving logic ...
            }

            DB::commit();

            Notification::make()
                ->title('Activity Created')
                ->body("'".$activity->title."' has been created successfully.")
                ->success()
                ->send();

            $this->redirect(ActivityResource::getUrl('index'), navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Error Creating Activity')
                ->body('An unexpected error occurred: '.$e->getMessage())
                ->danger()
                ->send();
            Log::error('Error creating activity: '.$e->getMessage(), ['exception' => $e]);
            $this->isSubmitting = false;
        }
    }

    // Helper Methods (can be reused from Resource or defined here)
    public function getCommonFileTypeOptions(): array
    {
        // Consider caching these if they don't change often
        return ActivityResource::getCommonFileTypeOptions();
    }

    public function getFormBuilderBlocks(): array
    {
        return ActivityResource::getFormBuilderBlocks();
    }

    // Methods for Repeater (Roles)
    public function addRole(): void
    {
        $this->roles[] = ['name' => '', 'description' => ''];
        // Reset validation for the roles array potentially
        // $this->resetValidation('roles'); // Check Livewire docs for specifics
    }

    public function removeRole(int $index): void
    {
        // Prevent removing if only one role exists and it's required? Optional.
        if (isset($this->roles[$index])) {
            unset($this->roles[$index]);
            $this->roles = array_values($this->roles); // Re-index
        }
    }

    public function render(): View
    {
        // Pass team settings to the view for conditional display
        $isCollege = $this->currentTeam?->usesCollegeGrading() ?? false;
        $isShs = $this->currentTeam?->usesShsGrading() ?? false;
        $isCollegeTerm = $this->currentTeam?->usesCollegeTermGrading() ?? false;
        $isCollegeGwa = $this->currentTeam?->usesCollegeGwaGrading() ?? false;

        return view('livewire.activity-creator', [
            'templatesData' => $this->templates,
            'activityTypeOptions' => $this->activityTypes,
            'commonFileTypeOptions' => $this->getCommonFileTypeOptions(),
            'formBuilderBlocks' => $this->getFormBuilderBlocks(),
            'visualElements' => $this->visualElements,
            'colorThemes' => $this->colorThemes,
            // Pass team settings
            'isCollege' => $isCollege,
            'isShs' => $isShs,
            'isCollegeTerm' => $isCollegeTerm,
            'isCollegeGwa' => $isCollegeGwa,
            'shsComponentOptions' => self::getShsComponentOptions(),
            'collegeTermOptions' => self::getCollegeTermOptions(),
        ]);
    }

    // Add helpers for SHS/College options if needed for selects in the view
    public static function getShsComponentOptions(): array
    {
        return [
            Activity::COMPONENT_WRITTEN_WORK => 'Written Work (WW)',
            Activity::COMPONENT_PERFORMANCE_TASK => 'Performance Task (PT)',
            Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'Quarterly Assessment (QA)',
        ];
    }

    public static function getCollegeTermOptions(): array
    {
        return [
            Activity::TERM_PRELIM => 'Prelim',
            Activity::TERM_MIDTERM => 'Midterm',
            Activity::TERM_FINAL => 'Final',
        ];
    }
}
