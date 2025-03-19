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

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Classroom Management';
    protected static ?string $navigationLabel = 'Students';
    protected static ?int $navigationSort = 2;


    public static function getNavigationBadge(): ?string
    {
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
        return parent::getEloquentQuery()
            ->where('team_id', Auth::user()->currentTeam->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('team_id')
                    ->default(fn () => Auth::user()->currentTeam->id),

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
                            ->helperText('School-assigned student ID'),

                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                                'prefer_not_to_say' => 'Prefer not to say',
                            ]),

                        DatePicker::make('birth_date')
                            ->label('Birth Date'),

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

                Tables\Actions\Action::make('link_user')
                    ->label('Link to User')
                    ->icon('heroicon-o-link')
                    ->form([
                        Select::make('user_id')
                            ->label('Select User')
                            ->options(function () {
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
                    ->visible(fn (Student $record) => $record->user_id === null),

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
                    ->visible(fn (Student $record) => $record->user_id !== null),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitySubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
