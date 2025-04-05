<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\Support\Document;
use Prism\Prism\ValueObjects\Messages\UserMessage; // Import Dashboard page
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput; // Import Team model

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Classroom Management';

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::user()->currentTeam) {
            return '0';
        }

        return (string) static::getModel()::query()
            ->where('team_id', Auth::user()->currentTeam->id)
            ->where('status', 'active')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        if (! $user->currentTeam) {
            // Return an empty query if no team is selected to avoid errors
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Or ->where('team_id', null) if appropriate
        }

        return parent::getEloquentQuery()->where(
            'team_id',
            $user->currentTeam->id
        );
    }

    public static function form(Form $form): Form
    {
        $teamId = Auth::user()->currentTeam?->id;

        return $form->schema([
            Hidden::make('team_id')->default(fn () => $teamId),

            Section::make('Student Information')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),

                    TextInput::make('email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText(
                            'If provided, this email can be used to link to a user account'
                        ),

                    TextInput::make('student_id')
                        ->label('Student ID')
                        ->maxLength(255)
                        ->helperText(
                            'School-assigned student ID ( if available )'
                        ),

                    Select::make('gender')->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                        'prefer_not_to_say' => 'Prefer not to say',
                    ]),
                    PhoneInput::make('phone')
                        ->label('Phone Number')
                        // ->maxLength(255)
                        ->onlyCountries(['ph'])
                        ->showFlags(false)
                        ->disallowDropdown()
                        ->helperText('student phone number ( if available )'),
                    // DatePicker::make('birth_date')
                    //     ->label('Birth Date'),

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'graduated' => 'Graduated',
                        ])
                        ->default('active')
                        ->required(),
                ])
                ->columns(2),

            Section::make('Additional Information')->schema([
                Textarea::make('notes')->maxLength(65535)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),

                TextColumn::make('email')->searchable()->sortable(),

                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable(),

                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'danger' => 'inactive',
                    'warning' => 'graduated',
                ]),

                TextColumn::make('user.name')
                    ->label('Linked User')
                    ->default('Not linked')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'graduated' => 'Graduated',
                ]),

                SelectFilter::make('gender')->options([
                    'male' => 'Male',
                    'female' => 'Female',
                    'other' => 'Other',
                    'prefer_not_to_say' => 'Prefer not to say',
                ]),

                Tables\Filters\Filter::make('has_user')
                    ->label('Linked to User')
                    ->query(
                        fn (Builder $query) => $query->whereNotNull('user_id')
                    ),

                Tables\Filters\Filter::make('no_user')
                    ->label('Not Linked to User')
                    ->query(fn (Builder $query) => $query->whereNull('user_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('link_user')
                    ->label('Link to User')
                    ->icon('heroicon-o-link')
                    ->form([
                        Select::make('user_id')
                            ->label('Select User')
                            ->options(function () {
                                if (! Auth::user()->currentTeam) {
                                    return [];
                                }

                                return User::whereHas('teams', function (
                                    $query
                                ) {
                                    $query->where(
                                        'teams.id',
                                        Auth::user()->currentTeam->id
                                    );
                                })->pluck('name', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (Student $record, array $data): void {
                        $user = User::find($data['user_id']);

                        if ($user) {
                            $record->update([
                                'user_id' => $user->id,
                                'email' => $user->email,
                            ]);

                            Notification::make()
                                ->title('Student Linked')
                                ->body(
                                    "Student has been linked to user {$user->name}"
                                )
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(
                        fn (Student $record) => $record->user_id === null &&
                            Auth::user()->can('manageUserLinks', $record)
                    ),

                Tables\Actions\Action::make('unlink_user')
                    ->label('Unlink User')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Student $record): void {
                        $record->update([
                            'user_id' => null,
                        ]);

                        Notification::make()
                            ->title('User Unlinked')
                            ->body(
                                'Student has been unlinked from user account'
                            )
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn (Student $record) => $record->user_id !== null &&
                            Auth::user()->can('manageUserLinks', $record)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('change_status')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'graduated' => 'Graduated',
                                ])
                                ->required(),
                        ])
                        ->action(function (
                            \Illuminate\Support\Collection $records,
                            array $data
                        ): void {
                            $records->each(function (Student $record) use (
                                $data
                            ) {
                                $record->update(['status' => $data['status']]);
                            });

                            Notification::make()
                                ->title('Status Updated')
                                ->body('Selected students have been updated')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import_students')
                    ->label('Import Students')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('document')
                            ->label('Upload Gradesheet')
                            ->acceptedFileTypes(['text/csv', 'application/pdf'])
                            ->helperText(
                                'Upload a CSV or PDF file containing student information (name required, email/student_id/gender optional).'
                            )
                            ->required()
                            ->maxSize(5120) // 5MB
                            ->directory('temp-imports'),
                    ])
                    ->action(function (array $data) {
                        // Removed void return type hint to allow redirect
                        $user = Auth::user();
                        $team = $user->currentTeam; // Get the team object

                        if (! $team) {
                            Notification::make()
                                ->title('Error')
                                ->body(
                                    'You must select a team before importing students.'
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        // Correct file path handling (use Storage facade consistently)
                        $filePath = Storage::disk('public')->path(
                            $data['document']
                        );
                        if (! file_exists($filePath)) {
                            Notification::make()
                                ->title('Error')
                                ->body(
                                    'Could not locate the uploaded file. Please check storage configuration and try again.'
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        // Get file mime type
                        // $mimeType = mime_content_type($filePath); // Not needed for Prism

                        // Define the schema for student data
                        $studentSchema = new ObjectSchema(
                            name: 'student',
                            description: 'Student information',
                            properties: [
                                new StringSchema('name', 'Student full name'),
                                new StringSchema(
                                    'email',
                                    'Student email address (if available, otherwise null)'
                                ),
                                new StringSchema(
                                    'student_id',
                                    'School-assigned student ID (if available, otherwise null)'
                                ),
                                new StringSchema(
                                    'gender',
                                    'Student gender (male, female, other, prefer_not_to_say, or null if not available)'
                                ),
                            ],
                            requiredFields: ['name']
                        );

                        $studentsSchema = new ArraySchema(
                            name: 'students',
                            description: 'List of students extracted from the document',
                            items: $studentSchema
                        );

                        // Process the document with Prism using structured output
                        try {
                            Notification::make()
                                ->title('Processing Import...')
                                ->body(
                                    'Analyzing document and extracting student data. This may take a moment.'
                                )
                                ->info()
                                ->send();

                            $response = Prism::structured()
                                ->using(Provider::Gemini, 'gemini-2.0-flash') // Ensure model is suitable
                                ->withSchema($studentsSchema)
                                ->withMessages([
                                    new UserMessage(
                                        'Analyze this student gradesheet document and extract student information. '.
                                            "Focus on extracting each student's full name (required). Also extract email, student ID, ".
                                            'and gender (male, female, other, prefer_not_to_say) if explicitly available, otherwise return null for those fields. '.
                                            'Return the data as a structured list of student objects.',
                                        [Document::fromPath($filePath)]
                                    ),
                                ])
                                ->asStructured();

                            // Get the structured data directly
                            $studentsData = $response->structured; // This should be the array

                            if (empty($studentsData)) {
                                Notification::make()
                                    ->title('Extraction Issue')
                                    ->body(
                                        'Could not extract student data or the document was empty/unreadable. Please check the file format and content.'
                                    )
                                    ->warning()
                                    ->send();
                                // Clean up the temp file even on failure
                                Storage::disk('public')->delete(
                                    $data['document']
                                );

                                return;
                            }

                            // Import students
                            $importCount = 0;
                            $skippedCount = 0;
                            $teamId = $team->id;

                            foreach ($studentsData as $studentData) {
                                // Basic validation: Ensure name is present and is a string
                                if (
                                    empty($studentData['name']) ||
                                    ! is_string($studentData['name'])
                                ) {
                                    \Illuminate\Support\Facades\Log::warning(
                                        'Skipping student import due to missing/invalid name',
                                        ['data' => $studentData]
                                    );
                                    $skippedCount++;

                                    continue;
                                }

                                // Clean up potential whitespace
                                $studentName = trim($studentData['name']);
                                if (empty($studentName)) {
                                    $skippedCount++;

                                    continue;
                                }

                                // Check if student with this name already exists in the team
                                $existingStudent = Student::where(
                                    'team_id',
                                    $teamId
                                )
                                    ->where('name', $studentName) // Use trimmed name
                                    ->exists(); // Just check existence

                                if (! $existingStudent) {
                                    Student::create([
                                        'team_id' => $teamId,
                                        'name' => $studentName,
                                        'email' => isset($studentData['email']) &&
                                            is_string($studentData['email'])
                                                ? trim($studentData['email'])
                                                : null,
                                        'student_id' => isset($studentData['student_id']) &&
                                            is_string(
                                                $studentData['student_id']
                                            )
                                                ? trim(
                                                    $studentData['student_id']
                                                )
                                                : null,
                                        'gender' => isset($studentData['gender']) &&
                                            in_array($studentData['gender'], [
                                                'male',
                                                'female',
                                                'other',
                                                'prefer_not_to_say',
                                            ])
                                                ? $studentData['gender']
                                                : null,
                                        'status' => 'active',
                                    ]);
                                    $importCount++;
                                } else {
                                    $skippedCount++;
                                }
                            }

                            // Clean up the temp file
                            Storage::disk('public')->delete($data['document']);

                            $notificationMessage = "Successfully imported {$importCount} new students.";
                            if ($skippedCount > 0) {
                                $notificationMessage .= " Skipped {$skippedCount} duplicates or invalid entries.";
                            }

                            Notification::make()
                                ->title('Import Complete')
                                ->body($notificationMessage)
                                ->success()
                                ->send();

                            // --- Onboarding Check ---
                            $team->refresh(); // Refresh team data to get latest onboarding_step
                            $currentStudentCount = $team->students()->count();
                            $onboardingStep = (int) $team->onboarding_step;

                            if (
                                $onboardingStep <= 1 &&
                                $currentStudentCount >=
                                    Dashboard::ONBOARDING_STUDENT_THRESHOLD
                            ) {
                                // Mark step 1 as complete if it wasn't already
                                if ($onboardingStep < 1) {
                                    $team->update(['onboarding_step' => 1]);
                                }
                                // Flash session and reload the current page to show step 2 modal
                                session()->flash(
                                    'trigger_onboarding_check',
                                    true
                                );

                                // Reload the current Filament page
                                return redirect(request()->header('Referer'));
                            }
                            // --- End Onboarding Check ---
                        } catch (\Throwable $e) {
                            // Catch Throwable for broader error handling
                            // Clean up the temp file on error
                            Storage::disk('public')->delete($data['document']);

                            \Illuminate\Support\Facades\Log::error(
                                'Student Import Failed',
                                [
                                    'team_id' => $teamId ?? 'N/A',
                                    'user_id' => $user->id,
                                    'file' => $data['document'],
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(), // Optional: for detailed debugging
                                ]
                            );

                            Notification::make()
                                ->title('Import Failed')
                                ->body(
                                    'An error occurred during processing: '.
                                        $e->getMessage()
                                )
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ActivitySubmissionsRelationManager::class];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Student Information')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                    TextEntry::make('student_id')->label('Student ID'),
                    TextEntry::make('gender')->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                            'prefer_not_to_say' => 'Prefer not to say',
                            default => $state,
                        }
                    ),
                    TextEntry::make('birth_date')->date(),
                    TextEntry::make('status')->badge()->color(
                        fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'inactive' => 'danger',
                            'graduated' => 'warning',
                            default => 'gray',
                        }
                    ),
                ])
                ->columns(2),

            InfolistSection::make('Linked User Information')->schema([
                Grid::make(2)->schema([
                    IconEntry::make('user_id')
                        ->label('Linked to User')
                        ->icon(
                            fn ($record) => $record->user_id
                                ? 'heroicon-o-check-circle'
                                : 'heroicon-o-x-circle'
                        )
                        ->color(
                            fn ($record) => $record->user_id
                                ? 'success'
                                : 'danger'
                        )
                        ->size('lg'),

                    IconEntry::make('team_member_status')
                        ->label('Member of Current Team')
                        ->icon(function ($record) {
                            if (! $record->user_id) {
                                return 'heroicon-o-x-circle';
                            }
                            if (! Auth::user()->currentTeam) {
                                return 'heroicon-o-question-mark-circle';
                            }

                            $userInTeam = $record->user?->belongsToTeam(
                                Auth::user()->currentTeam
                            );

                            return $userInTeam
                                ? 'heroicon-o-check-circle'
                                : 'heroicon-o-x-circle';
                        })
                        ->color(function ($record) {
                            if (! $record->user_id) {
                                return 'danger';
                            }
                            if (! Auth::user()->currentTeam) {
                                return 'warning';
                            }

                            $userInTeam = $record->user?->belongsToTeam(
                                Auth::user()->currentTeam
                            );

                            return $userInTeam ? 'success' : 'danger';
                        })
                        ->size('lg'),
                ]),

                TextEntry::make('user.name')
                    ->label('User Name')
                    ->visible(fn ($record) => $record->user_id !== null),

                TextEntry::make('user.email')
                    ->label('User Email')
                    ->visible(fn ($record) => $record->user_id !== null),

                TextEntry::make('user.teams_count')
                    ->label('Number of Teams')
                    ->visible(fn ($record) => $record->user_id !== null)
                    ->formatStateUsing(
                        fn ($record) => $record->user?->teams->count() ?? 0
                    ),

                TextEntry::make('user_teams')
                    ->label('Team Memberships')
                    ->visible(fn ($record) => $record->user_id !== null)
                    ->formatStateUsing(function ($record) {
                        if (! $record->user) {
                            return 'N/A';
                        }
                        if (! Auth::user()->currentTeam) {
                            return $record->user->teams
                                ->pluck('name')
                                ->join(', ');
                        }

                        $teams = $record->user->teams->map(function (
                            $team
                        ) {
                            $isCurrent =
                                $team->id === Auth::user()->currentTeam->id;

                            return $isCurrent
                                ? "**{$team->name} (Current)**"
                                : $team->name;
                        });

                        return $teams->join(', ');
                    })
                    ->markdown(),
            ]),

            InfolistSection::make('Additional Information')
                ->schema([
                    TextEntry::make('notes')->columnSpanFull(),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('updated_at')->dateTime(),
                ])
                ->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\ViewStudent::route('/{record}'),
        ];
    }
}
