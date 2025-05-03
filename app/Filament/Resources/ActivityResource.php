<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\Pages\CreateActivityCustom;
use App\Filament\Resources\ActivityResource\RelationManagers;
use App\Models\Activity; // Added User import
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Builder as FormBuilder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker; // Added Model import
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab; // Alias to avoid conflict
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; // Added Notification import
use Illuminate\Database\Eloquent\Model; // Added Str import
use Illuminate\Support\Facades\Auth; // Import the new custom resource page
use Illuminate\Support\Str; // Added Team import

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Classroom Tools';

    protected static ?string $navigationLabel = 'Activities';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        // Start with activities belonging to the current team
        $query = parent::getEloquentQuery()->where('team_id', $team->id);

        return $query;
    }

    public static function form(Form $form): Form
    {
        // Get current team settings to control visibility
        // Note: This assumes the context is within an authenticated user's session
        $team = Auth::user()?->currentTeam;
        $isCollege = $team?->usesCollegeGrading() ?? false;
        $isShs = $team?->usesShsGrading() ?? false;
        $isCollegeTerm = $team?->usesCollegeTermGrading() ?? false;
        $isCollegeGwa = $team?->usesCollegeGwaGrading() ?? false;

        return $form->schema([
            Tabs::make('Activity Details')
                ->tabs([
                    Tab::make('Details')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Basic Information')
                                ->schema([
                                    Hidden::make('teacher_id')->default(
                                        fn () => Auth::id()
                                    ),
                                    Hidden::make('team_id')->default(
                                        fn () => Auth::user()?->currentTeam?->id
                                    ),
                                    TextInput::make('title')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Select::make('activity_type_id')
                                        ->relationship('activityType', 'name')
                                        ->label('Activity Type')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    Select::make('status')
                                        ->options([
                                            'draft' => 'Draft',
                                            'published' => 'Published',
                                            'archived' => 'Archived',
                                        ])
                                        ->default('draft')
                                        ->required(),
                                    DateTimePicker::make('due_date') // Changed from deadline
                                        ->label('Submission Deadline')
                                        ->placeholder(
                                            'Optional: Set a deadline'
                                        )
                                        ->native(false)
                                        ->weekStartsOnSunday(),
                                ])
                                ->columns(2),

                            Section::make('Content')->schema([
                                RichEditor::make('description')
                                    ->label('Description')
                                    ->helperText(
                                        'Provide a general overview of the activity.'
                                    )
                                    ->columnSpanFull(),
                                RichEditor::make('instructions')
                                    ->label('Instructions')
                                    ->helperText(
                                        'Provide detailed steps or guidance for students.'
                                    )
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    Tab::make('Configuration')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Section::make('Activity Setup')
                                ->schema([
                                    Radio::make('mode')
                                        ->label('Activity Mode')
                                        ->options([
                                            'individual' => 'Individual Activity',
                                            'group' => 'Group Activity',
                                            'take_home' => 'Take-Home Activity',
                                        ])
                                        ->required()
                                        ->default('individual')
                                        ->live(), // Use live() for reactivity

                                    // --- SHS Component Type ---
                                    Select::make('component_type')
                                        ->label('SHS Component')
                                        ->options([
                                            Activity::COMPONENT_WRITTEN_WORK => 'Written Work (WW)',
                                            Activity::COMPONENT_PERFORMANCE_TASK => 'Performance Task (PT)',
                                            Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'Quarterly Assessment (QA)',
                                        ])
                                        ->placeholder('Select SHS Component')
                                        ->required(fn (): bool => $isShs) // Required only if team is SHS
                                        ->visible(fn (): bool => $isShs), // Visible only if team is SHS

                                    // --- College Term ---
                                    Select::make('term')
                                        ->label('College Term')
                                        ->options([
                                            Activity::TERM_PRELIM => 'Prelim',
                                            Activity::TERM_MIDTERM => 'Midterm',
                                            Activity::TERM_FINAL => 'Final',
                                        ])
                                        ->placeholder('Select College Term')
                                        // Required only if team uses College Term grading
                                        ->required(fn (): bool => $isCollegeTerm)
                                        // Visible only if team uses College Term grading
                                        ->visible(fn (): bool => $isCollegeTerm),

                                    // --- College Credit Units ---
                                    TextInput::make('credit_units')
                                        ->label('Credit Units')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01) // Allow decimals like 1.5, 0.75
                                        ->placeholder('e.g., 3.00')
                                        ->helperText(
                                            'Units used for College GWA calculation.'
                                        )
                                        // Required only if team uses College GWA grading
                                        ->required(fn (): bool => $isCollegeGwa)
                                        // Visible only if team is College (Term or GWA)
                                        ->visible(fn (): bool => $isCollege),

                                    TextInput::make('total_points')
                                        ->label('Total Points')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(10),

                                    // --- General Format (Quiz, Assignment etc.) ---
                                    // Keep this section as is
                                    Select::make('format')
                                        ->label('Activity Format')
                                        ->options([
                                            'quiz' => 'Quiz',
                                            'assignment' => 'Assignment',
                                            'reporting' => 'Reporting',
                                            'presentation' => 'Presentation',
                                            'discussion' => 'Discussion',
                                            'project' => 'Project',
                                            'other' => 'Other',
                                        ])
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(
                                            fn (
                                                Set $set,
                                                ?string $state
                                            ) => $set(
                                                'custom_format',
                                                $state === 'other' ? '' : null
                                            )
                                        ),
                                    TextInput::make('custom_format')
                                        ->label('Custom Format Name')
                                        ->placeholder('Specify custom format')
                                        ->visible(
                                            fn (Get $get): bool => $get(
                                                'format'
                                            ) === 'other'
                                        )
                                        ->required(
                                            fn (Get $get): bool => $get(
                                                'format'
                                            ) === 'other'
                                        ), // Make required if 'other'
                                ])
                                ->columns(2),

                            Section::make('Group Settings')
                                ->schema([
                                    TextInput::make('group_count')
                                        ->label('Approximate Number of Groups')
                                        ->helperText(
                                            'Used for auto-assignment. Groups can be manually adjusted later.'
                                        )
                                        ->numeric()
                                        ->minValue(2)
                                        ->default(4)
                                        ->visible(
                                            fn (Get $get) => $get('mode') ===
                                                'group'
                                        ),
                                    // Auto-assign toggle might be better as an action in RelationManager
                                    // Toggle::make('auto_assign_groups')
                                    //    ->label('Auto-assign Students to Groups on Create')
                                    //    ->default(true)
                                    //    ->visible(fn (Get $get) => $get('mode') === 'group'),
                                    Repeater::make('roles') // Added Roles from Wizard
                                        ->relationship()
                                        ->label('Group Roles (Optional)')
                                        ->schema([
                                            TextInput::make('name')
                                                ->required()
                                                ->label('Role Name')
                                                ->placeholder(
                                                    'e.g., Leader, Recorder, Presenter'
                                                ),
                                            Textarea::make('description')
                                                ->label('Role Description')
                                                ->placeholder(
                                                    'Describe the responsibilities of this role'
                                                ),
                                        ])
                                        ->columns(2)
                                        ->itemLabel(
                                            fn (array $state): ?string => $state[
                                                'name'
                                            ] ?? null
                                        )
                                        ->addActionLabel('Add Role')
                                        ->collapsible()
                                        ->collapsed()
                                        ->defaultItems(0)
                                        ->visible(
                                            fn (Get $get) => $get('mode') ===
                                                'group'
                                        ),
                                ])
                                ->visible(
                                    fn (Get $get) => $get('mode') === 'group'
                                ), // Section only visible for group mode
                        ]),

                    Tab::make('Submission Settings')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->schema([
                            Section::make('Submission Type')->schema([
                                Radio::make('submission_type')
                                    ->label(
                                        'How will students submit this activity?'
                                    )
                                    ->options([
                                        'resource' => 'File Upload / Text Entry',
                                        'form' => 'Online Form (Structured Questions)',
                                        'manual' => 'Manual Grading Only (No Student Submission)',
                                    ])
                                    ->descriptions([
                                        'resource' => 'Students upload files (docs, images, etc.) or type directly.',
                                        'form' => 'Students fill out a form with questions you define.',
                                        'manual' => 'Teacher records scores manually, no online submission from students.',
                                    ])
                                    ->default('resource')
                                    ->required()
                                    ->reactive(),
                            ]),

                            Section::make('File Upload / Text Entry Options')
                                ->schema([
                                    Toggle::make('allow_file_uploads')
                                        ->label('Allow File Uploads')
                                        ->default(true)
                                        ->reactive(),
                                    Toggle::make('allow_text_entry') // Added option for text entry
                                        ->label('Allow Direct Text Entry')
                                        ->default(false)
                                        ->helperText(
                                            'Students can type their submission directly into a text box.'
                                        ),

                                    CheckboxList::make('allowed_file_types')
                                        ->label('Allowed File Types')
                                        ->helperText(
                                            'Select which file types are accepted. Leave blank to allow any.'
                                        )
                                        ->options(
                                            self::getCommonFileTypeOptions()
                                        )
                                        ->columns(2)
                                        ->gridDirection('row')
                                        ->bulkToggleable()
                                        ->visible(
                                            fn (Get $get): bool => $get(
                                                'allow_file_uploads'
                                            ) === true
                                        ),
                                    TextInput::make('max_file_size')
                                        ->label(
                                            'Maximum File Size per File (MB)'
                                        )
                                        ->numeric()
                                        ->default(10)
                                        ->minValue(1)
                                        ->helperText(
                                            'Set the limit for each uploaded file.'
                                        )
                                        ->visible(
                                            fn (Get $get): bool => $get(
                                                'allow_file_uploads'
                                            ) === true
                                        ),
                                ])
                                ->visible(
                                    fn (Get $get): bool => $get(
                                        'submission_type'
                                    ) === 'resource'
                                ),

                            Section::make('Online Form Structure')
                                ->schema([
                                    Placeholder::make(
                                        'form_builder_info'
                                    )->content(
                                        'Define the questions and fields students will need to fill out.'
                                    ),
                                    FormBuilder::make('form_structure')
                                        ->label('Form Questions')
                                        ->blocks(self::getFormBuilderBlocks())
                                        ->collapsible()
                                        ->collapsed() // Start collapsed
                                        ->addActionLabel('Add Form Field'),
                                ])
                                ->visible(
                                    fn (Get $get): bool => $get(
                                        'submission_type'
                                    ) === 'form'
                                ),

                            Section::make('Teacher Actions')
                                ->schema([
                                    Toggle::make('allow_teacher_submission')
                                        ->label('Allow Teacher Submissions')
                                        ->helperText(
                                            'Enable this if teachers need to submit work on behalf of students.'
                                        )
                                        ->default(false),
                                ])
                                ->visible(
                                    fn (Get $get): bool => $get(
                                        'submission_type'
                                    ) !== 'manual'
                                ),
                        ]),

                    // Resources Tab - Only on Edit Page
                    Tab::make('Resources for Students')
                        ->icon('heroicon-o-book-open')
                        ->visible(
                            fn (string $operation): bool => $operation === 'edit'
                        ) // Only show on edit
                        ->schema([
                            Section::make('Activity Resources')->schema([
                                Placeholder::make('resources_info')->content(
                                    'Upload files (like worksheets, readings, templates) that students can access for this activity.'
                                ),

                                Repeater::make('resources')
                                    ->relationship() // Assumes an Activity hasMany Resource relationship
                                    ->label('Attached Resources')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Resource Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText(
                                                'A descriptive name for the file (e.g., "Worksheet 1", "Reading Material").'
                                            ),
                                        Textarea::make('description')
                                            ->label('Description (Optional)')
                                            ->rows(2),
                                        FileUpload::make('file_path')
                                            ->label('File')
                                            ->disk('public') // Ensure this disk is configured and publicly accessible
                                            ->directory(function (
                                                Get $get,
                                                Set $set,
                                                ?Model $record
                                            ) {
                                                // $record is the Activity model instance ONLY on edit
                                                if ($record) {
                                                    return "activity_resources/{$record->id}";
                                                }

                                                // Handle case during creation if needed, though this tab is hidden
                                                return 'activity_resources/temp';
                                            })
                                            ->visibility('public') // Make sure files are accessible
                                            ->required()
                                            ->maxSize(20480) // 20MB example max size
                                            ->afterStateUpdated(function (
                                                Set $set,
                                                $state
                                            ): void {
                                                // $state is an instance of Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                                                if ($state) {
                                                    $set(
                                                        'file_name',
                                                        $state->getClientOriginalName()
                                                    );
                                                    $set(
                                                        'file_size',
                                                        $state->getSize()
                                                    );
                                                    $set(
                                                        'file_type',
                                                        $state->getMimeType()
                                                    );
                                                } else {
                                                    $set('file_name', null);
                                                    $set('file_size', null);
                                                    $set('file_type', null);
                                                }
                                            })
                                            ->reactive(),
                                        Hidden::make('file_name'),
                                        Hidden::make('file_size'),
                                        Hidden::make('file_type'),
                                        Hidden::make('user_id')->default(
                                            fn () => Auth::id()
                                        ), // Track who uploaded
                                        Toggle::make('is_public') // Renamed for clarity? Or keep as is?
                                            ->label('Visible to Students')
                                            ->default(true),
                                    ])
                                    ->itemLabel(
                                        fn (array $state): string => $state[
                                            'name'
                                        ] ?? 'New Resource'
                                    )
                                    ->addActionLabel('Add Resource File')
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed()
                                    ->grid(1), // Ensure repeater takes full width if needed
                            ]),
                        ]),
                ])
                ->columnSpanFull(), // Ensure Tabs take full width
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(
                        fn (Activity $record): ?string => Str::limit(
                            $record->description,
                            50
                        )
                    ),
                Tables\Columns\TextColumn::make('activityType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                // --- Conditional Term/Component Column ---
                Tables\Columns\BadgeColumn::make('term_or_component')
                    ->label('Term/Component')
                    ->getStateUsing(function (?Activity $record) {
                        if (! $record) {
                            return null;
                        }
                        if ($record->term) {
                            return $record->term_description;
                        } elseif ($record->component_type) {
                            return $record->component_type_code;
                        }

                        return null;
                    })
                    ->colors([
                        // SHS Colors
                        'info' => Activity::COMPONENT_WRITTEN_WORK,
                        'warning' => Activity::COMPONENT_PERFORMANCE_TASK,
                        'success' => Activity::COMPONENT_QUARTERLY_ASSESSMENT,
                        // College Colors
                        'teal' => Activity::TERM_PRELIM,
                        'purple' => Activity::TERM_MIDTERM,
                        'orange' => Activity::TERM_FINAL,
                    ])
                    ->formatStateUsing(function (
                        string $state,
                        ?Activity $record
                    ) {
                        if (! $record) {
                            return $state;
                        }
                        if ($record->term) {
                            return $record->term_description;
                        }
                        if ($record->component_type) {
                            return $record->component_type_code;
                        }

                        return $state;
                    })
                    ->visible(function (?Activity $record): bool {
                        if (! $record) {
                            return false;
                        }

                        return $record->term || $record->component_type;
                    })
                    ->searchable(
                        query: function (
                            Builder $query,
                            string $search
                        ): Builder {
                            return $query->where(function ($q) use ($search): void {
                                $q->where(
                                    'term',
                                    'like',
                                    "%{$search}%"
                                )->orWhere(
                                    'component_type',
                                    'like',
                                    "%{$search}%"
                                );
                            });
                        }
                    )
                    ->sortable(['term', 'component_type']),
                Tables\Columns\BadgeColumn::make('mode')
                    ->label('Mode')
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'take_home',
                    ])
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'individual' => 'Individual',
                            'group' => 'Group',
                            'take_home' => 'Take-Home',
                            default => Str::title(
                                str_replace('_', ' ', $state)
                            ),
                        }
                    ),
                Tables\Columns\TextColumn::make('total_points')
                    ->label('Points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_units')
                    ->label('Units')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(function (?Activity $record): bool {
                        if (! $record || ! $record->team) {
                            return false;
                        }

                        return $record->team->usesCollegeGrading();
                    }),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'gray' => 'draft',
                    'success' => 'published',
                    'warning' => 'archived',
                ]),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Deadline')
                    ->placeholder('No deadline'),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ]),
                Tables\Filters\SelectFilter::make('mode')->options([
                    'individual' => 'Individual',
                    'group' => 'Group',
                    'take_home' => 'Take-Home',
                ]),
                Tables\Filters\SelectFilter::make('term')
                    ->label('College Term')
                    ->options([
                        Activity::TERM_PRELIM => 'Prelim',
                        Activity::TERM_MIDTERM => 'Midterm',
                        Activity::TERM_FINAL => 'Final',
                    ])
                    ->visible(
                        fn () => Auth::user()?->currentTeam?->usesCollegeTermGrading()
                    ),
                Tables\Filters\SelectFilter::make('component_type')
                    ->label('SHS Component')
                    ->options([
                        Activity::COMPONENT_WRITTEN_WORK => 'Written Work (WW)',
                        Activity::COMPONENT_PERFORMANCE_TASK => 'Performance Task (PT)',
                        Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'Quarterly Assessment (QA)',
                    ])
                    ->visible(
                        fn () => Auth::user()?->currentTeam?->usesShsGrading()
                    ),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Created By')
                    ->options(
                        fn () => User::whereHas(
                            'teams',
                            fn ($q) => $q->where(
                                'team_id',
                                Auth::user()->currentTeam->id
                            )
                        )
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('due_date')
                    ->label('Has Deadline')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (Activity $record) {
                            try {
                                $newActivity = $record->replicateWithRelations();
                                $newActivity->title =
                                    $record->title.' (Copy)';
                                $newActivity->status = 'draft';
                                $newActivity->created_at = now();
                                $newActivity->updated_at = now();
                                $newActivity->teacher_id = Auth::id();
                                $newActivity->save();

                                Notification::make()
                                    ->title('Activity Duplicated')
                                    ->success()
                                    ->send();

                                return redirect()->route(
                                    'filament.admin.resources.activities.edit',
                                    $newActivity
                                );
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Duplication Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (
                            \Illuminate\Support\Collection $records
                        ): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => 'published']);
                                $count++;
                            }
                            Notification::make()
                                ->title("Published {$count} activities.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('archive')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (
                            \Illuminate\Support\Collection $records
                        ): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => 'archived']);
                                $count++;
                            }
                            Notification::make()
                                ->title("Archived {$count} activities.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GroupsRelationManager::class,
            RelationManagers\StudentSubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            // Point 'create' route to the custom page
            'create-guide' => Pages\CreateActivityCustomGuide::route(
                '/create/guide'
            ),
            // Keep other routes if needed
            'create' => Pages\CreateActivity::route('/create'), // Keep standard if you need it
            'edit' => Pages\EditActivity::route('/{record}/edit'),
            // "custom-create" => CreateActivityCustom::route("/create-custom"), // Remove or rename if replaced
        ];
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user &&
            ($user->ownsTeam($user->currentTeam) ||
                $user->hasTeamRole($user->currentTeam, 'teacher'));
    }

    public static function getCommonFileTypeOptions(): array
    {
        return [
            'application/pdf' => 'PDF (.pdf)',
            'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word (.doc, .docx)',
            'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel (.xls, .xlsx)',
            'application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint (.ppt, .pptx)',
            'image/jpeg,image/png,image/gif,image/webp' => 'Images (.jpg, .png, .gif, .webp)',
            'video/mp4,video/quicktime,video/webm' => 'Videos (.mp4, .mov, .webm)',
            'audio/mpeg,audio/wav,audio/ogg' => 'Audio (.mp3, .wav, .ogg)',
            'text/plain' => 'Text Files (.txt)',
            'application/zip,application/x-rar-compressed' => 'Archives (.zip, .rar)',
        ];
    }

    public static function getFormBuilderBlocks(): array
    {
        return [
            Block::make('text')
                ->label('Text Input (Single Line)')
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->schema([
                    TextInput::make('name')
                        ->label('Field Name/ID')
                        ->required()
                        ->helperText('Unique identifier (e.g., question_1)'),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    Toggle::make('required')->default(false),
                ]),
            Block::make('textarea')
                ->label('Text Area (Multi-line)')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('name')->label('Field Name/ID')->required(),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    Toggle::make('required')->default(false),
                    TextInput::make('rows')
                        ->label('Number of Rows')
                        ->numeric()
                        ->default(5)
                        ->minValue(2),
                ]),
            Block::make('select')
                ->label('Dropdown / Multiple Choice')
                ->icon('heroicon-m-chevron-down')
                ->schema([
                    TextInput::make('name')->label('Field Name/ID')->required(),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    KeyValue::make('options')
                        ->label('Options')
                        ->keyLabel('Value')
                        ->valueLabel('Label')
                        ->addButtonLabel('Add Option')
                        ->required(),
                    Toggle::make('multiple')
                        ->label('Allow Multiple Selections')
                        ->default(false)
                        ->reactive(),
                    Toggle::make('required')->default(false),
                ]),
            Block::make('checkbox_list')
                ->label('Checkbox List')
                ->icon('heroicon-o-check')
                ->schema([
                    TextInput::make('name')->label('Field Name/ID')->required(),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    KeyValue::make('options')
                        ->label('Options')
                        ->keyLabel('Value')
                        ->valueLabel('Label')
                        ->addButtonLabel('Add Option')
                        ->required(),
                    Toggle::make('required')
                        ->label('Require at least one selection')
                        ->default(false),
                ]),
            Block::make('radio')
                ->label('Radio Buttons (Single Choice)')
                ->icon('heroicon-o-bars-4')
                ->schema([
                    TextInput::make('name')->label('Field Name/ID')->required(),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    KeyValue::make('options')
                        ->label('Options')
                        ->keyLabel('Value')
                        ->valueLabel('Label')
                        ->addButtonLabel('Add Option')
                        ->required(),
                    Toggle::make('required')->default(false),
                ]),
            Block::make('date')
                ->label('Date Input')
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    TextInput::make('name')->label('Field Name/ID')->required(),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    Toggle::make('required')->default(false),
                ]),
            Block::make('file')
                ->label('File Upload Field')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    TextInput::make('name')->label('Field Name/ID')->required(),
                    TextInput::make('label')
                        ->label('Question Label')
                        ->required(),
                    Textarea::make('help_text')
                        ->label('Help Text (Optional)')
                        ->rows(2),
                    TextInput::make('max_size')
                        ->label('Maximum File Size (MB)')
                        ->numeric()
                        ->default(5)
                        ->minValue(1),
                    TextInput::make('accepted_file_types')
                        ->label('Accepted File Types')
                        ->placeholder('e.g., .pdf,.doc,.jpg')
                        ->nullable()
                        ->helperText(
                            'Comma-separated list of extensions or MIME types.'
                        ),
                    Toggle::make('required')->default(false),
                ]),
        ];
    }
}
