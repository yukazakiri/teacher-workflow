<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Tables\View\TablesRenderHook;
use Symfony\Component\Console\Helper\TableStyle;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\Support\Document;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Classroom Management';
    protected static ?string $navigationLabel = 'Students';
    protected static ?int $navigationSort = 2;


    public static function getNavigationBadge(): ?string
    {
        if (!Auth::user()->currentTeam) {
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
        if (!Auth::user()->currentTeam) {
            return parent::getEloquentQuery()->where('team_id', null);
        }

        return parent::getEloquentQuery()
            ->where('team_id', Auth::user()->currentTeam->id);
    }

    public static function form(Form $form): Form
    {
        $teamId = Auth::user()->currentTeam?->id;

        return $form
            ->schema([
                Hidden::make('team_id')
                    ->default(fn () => $teamId),

                Section::make('Student Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('If provided, this email can be used to link to a user account'),

                        TextInput::make('student_id')
                            ->label('Student ID')
                            ->maxLength(255)
                            ->helperText('School-assigned student ID ( if available )'),

                        Select::make('gender')
                            ->options([
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

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->colors([
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
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'graduated' => 'Graduated',
                    ]),

                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                        'prefer_not_to_say' => 'Prefer not to say',
                    ]),

                Tables\Filters\Filter::make('has_user')
                    ->label('Linked to User')
                    ->query(fn (Builder $query) => $query->whereNotNull('user_id')),

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
                                if (!Auth::user()->currentTeam) {
                                    return [];
                                }

                                return User::whereHas('teams', function ($query) {
                                    $query->where('teams.id', Auth::user()->currentTeam->id);
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
                                ->body("Student has been linked to user {$user->name}")
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn (Student $record) => 
                        $record->user_id === null && 
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
                            ->body('Student has been unlinked from user account')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Student $record) => 
                        $record->user_id !== null && 
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
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $records->each(function (Student $record) use ($data) {
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
                            ->helperText('Upload a CSV or PDF file containing student information')
                            ->required()
                            ->maxSize(5120) // 5MB
                            ->directory('temp-imports'),
                    ])
                    ->action(function (array $data): void {
                        if (!Auth::user()->currentTeam) {
                            Notification::make()
                                ->title('Error')
                                ->body('You must select a team before importing students.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Fix file path handling
                        $filePath = Storage::disk('public')->path($data['document']);
                        
                        // Ensure the file exists
                        if (!file_exists($filePath)) {
                            // Try with different disk configuration
                            $filePath = Storage::disk('local')->path($data['document']);
                            
                            if (!file_exists($filePath)) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Could not locate the uploaded file. Please try again.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }
                        
                        // Get file mime type
                        $mimeType = mime_content_type($filePath);
                        
                        // Define the schema for student data
                        $studentSchema = new ObjectSchema(
                            name: 'student',
                            description: 'Student information',
                            properties: [
                                new StringSchema('name', 'Student full name'),
                                new StringSchema('email', 'Student email address (if available)'),
                                new StringSchema('student_id', 'School-assigned student ID (if available)'),
                                new StringSchema('gender', 'Student gender (male, female, other, or prefer_not_to_say)'),
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
                            $response = Prism::structured()
                                ->using(Provider::Gemini, 'gemini-2.0-flash')
                                ->withSchema($studentsSchema)
                                ->withMessages([
                                    new UserMessage(
                                        "Analyze this student gradesheet document and extract student information. " .
                                        "Extract each student's full name, email (if available), student ID (if available), " .
                                        "gender (if available). The gender should be one of: male, female, other, prefer_not_to_say. " .
                                        "Return the data as a structured list of student objects.",
                                        [Document::fromPath($filePath)]
                                    ),
                                ])
                                ->asStructured();
                            
                            // Get the structured data directly
                            $studentsData = $response->structured;
                            
                            if (empty($studentsData)) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Could not extract student data from the document. Please check the file format.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            // Import students
                            $importCount = 0;
                            $teamId = Auth::user()->currentTeam->id;
                            
                            foreach ($studentsData as $studentData) {
                                // Make sure name is provided
                                if (empty($studentData['name'])) {
                                    continue;
                                }
                                
                                // Check if student with this name already exists in the team
                                $existingStudent = Student::where('team_id', $teamId)
                                    ->where('name', $studentData['name'])
                                    ->first();
                                
                                if (!$existingStudent) {
                                    Student::create([
                                        'team_id' => $teamId,
                                        'name' => $studentData['name'],
                                        'email' => $studentData['email'] ?? null,
                                        'student_id' => $studentData['student_id'] ?? null,
                                        'gender' => $studentData['gender'] ?? null,
                                        'status' => 'active',
                                    ]);
                                    $importCount++;
                                }
                            }
                            
                            // Clean up the temp file
                            Storage::delete($data['document']);
                            
                            Notification::make()
                                ->title('Import Successful')
                                ->body("Successfully imported {$importCount} new students.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body('Error processing document: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitySubmissionsRelationManager::class,
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Student Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('student_id')
                            ->label('Student ID'),
                        TextEntry::make('gender')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                                'prefer_not_to_say' => 'Prefer not to say',
                                default => $state,
                            }),
                        TextEntry::make('birth_date')
                            ->date(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                'graduated' => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                InfolistSection::make('Linked User Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('user_id')
                                    ->label('Linked to User')
                                    ->icon(fn ($record) => $record->user_id ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->color(fn ($record) => $record->user_id ? 'success' : 'danger')
                                    ->size('lg'),

                                IconEntry::make('team_member_status')
                                    ->label('Member of Current Team')
                                    ->icon(function ($record) {
                                        if (!$record->user_id) return 'heroicon-o-x-circle';
                                        if (!Auth::user()->currentTeam) return 'heroicon-o-question-mark-circle';

                                        $userInTeam = $record->user?->belongsToTeam(Auth::user()->currentTeam);
                                        return $userInTeam ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                                    })
                                    ->color(function ($record) {
                                        if (!$record->user_id) return 'danger';
                                        if (!Auth::user()->currentTeam) return 'warning';

                                        $userInTeam = $record->user?->belongsToTeam(Auth::user()->currentTeam);
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
                            ->formatStateUsing(fn ($record) => $record->user?->teams->count() ?? 0),

                        TextEntry::make('user_teams')
                            ->label('Team Memberships')
                            ->visible(fn ($record) => $record->user_id !== null)
                            ->formatStateUsing(function ($record) {
                                if (!$record->user) return 'N/A';
                                if (!Auth::user()->currentTeam) return $record->user->teams->pluck('name')->join(', ');

                                $teams = $record->user->teams->map(function ($team) use ($record) {
                                    $isCurrent = $team->id === Auth::user()->currentTeam->id;
                                    return $isCurrent ? "**{$team->name} (Current)**" : $team->name;
                                });

                                return $teams->join(', ');
                            })
                            ->markdown(),
                    ]),

                InfolistSection::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
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
