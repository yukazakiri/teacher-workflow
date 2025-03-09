<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use App\Models\ActivityType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;

class ActivityResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('team', function (Builder $query) {
                $query->where('id', Auth::user()->currentTeam->id);
            })
            ->where(function (Builder $query) {
                // Show all activities for the team owner, but only their own activities for other team members
                $isOwner = Auth::user()->currentTeam->user_id === Auth::id();
                if (!$isOwner) {
                    $query->where('teacher_id', Auth::id());
                }
            });
    }
    
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Classroom Tools';
    protected static ?string $navigationLabel = 'Activities';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Hidden::make('teacher_id')
                                ->default(fn () => Auth::id()),
                            Hidden::make('team_id')
                                ->default(fn () => Auth::user()->currentTeam->id),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter activity title')
                                ->columnSpan(2),
                            RichEditor::make('description')
                                ->placeholder('Enter activity description')
                                ->columnSpan(2),
                            RichEditor::make('instructions')
                                ->placeholder('Enter detailed instructions for students')
                                ->columnSpan(2),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                    'archived' => 'Archived',
                                ])
                                ->default('draft')
                                ->required(),
                            DateTimePicker::make('deadline')
                                ->label('Submission Deadline')
                                ->placeholder('Select deadline (optional)')
                                ->timezone('Asia/Manila'),
                        ])
                        ->columns(2),
                    Step::make('Activity Configuration')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Section::make('Activity Type')
                                ->schema([
                                    Select::make('activity_type_id')
                                        ->relationship('activityType', 'name')
                                        ->required()
                                        ->label('Activity Type')
                                        ->helperText('Select the type of activity you want to create'),
                                    Radio::make('mode')
                                        ->label('Activity Mode')
                                        ->options([
                                            'individual' => 'Individual Activity',
                                            'group' => 'Group Activity',
                                            'take_home' => 'Take-Home Activity',
                                        ])
                                        ->required()
                                        ->default('individual')
                                        ->reactive(),
                                    Radio::make('category')
                                        ->label('Activity Category')
                                        ->options([
                                            'written' => 'Written Activity',
                                            'performance' => 'Performance Activity',
                                        ])
                                        ->required()
                                        ->default('written'),
                                ])
                                ->columns(1),
                            Section::make('Format & Grading')
                                ->schema([
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
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn (callable $set, $state) => $set('custom_format', $state === 'other' ? '' : null)),
                                    TextInput::make('custom_format')
                                        ->label('Custom Format')
                                        ->placeholder('Specify custom format')
                                        ->visible(fn (callable $get) => $get('format') === 'other'),
                                    TextInput::make('total_points')
                                        ->label('Total Points')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->default(10),
                                ])
                                ->columns(1),
                        ])
                        ->columns(2),
                    Step::make('Group Settings')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Section::make('Group Configuration')
                                ->schema([
                                    TextInput::make('group_count')
                                        ->label('Number of Groups')
                                        ->numeric()
                                        ->minValue(2)
                                        ->default(4)
                                        ->visible(fn (callable $get) => $get('mode') === 'group'),
                                    Toggle::make('auto_assign_groups')
                                        ->label('Auto-assign Students to Groups')
                                        ->default(true)
                                        ->visible(fn (callable $get) => $get('mode') === 'group'),
                                    Repeater::make('roles')
                                        ->relationship()
                                        ->schema([
                                            TextInput::make('name')
                                                ->required()
                                                ->label('Role Name')
                                                ->placeholder('e.g., Leader, Recorder, Presenter'),
                                            Textarea::make('description')
                                                ->label('Role Description')
                                                ->placeholder('Describe the responsibilities of this role'),
                                        ])
                                        ->columns(2)
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                        ->defaultItems(0)
                                        ->visible(fn (callable $get) => $get('mode') === 'group')
                                        ->createItemButtonLabel('Add Role'),
                                ])
                                ->visible(fn (callable $get) => $get('mode') === 'group'),
                            Placeholder::make('individual_note')
                                ->label('Individual Activity')
                                ->content('This activity will be assigned to each student individually.')
                                ->visible(fn (callable $get) => $get('mode') === 'individual'),
                            Placeholder::make('take_home_note')
                                ->label('Take-Home Activity')
                                ->content('This activity will be completed by students outside of class time. Make sure to set a deadline.')
                                ->visible(fn (callable $get) => $get('mode') === 'take_home'),
                        ]),
                ])
                ->skippable()
                ->persistStepInQueryString()
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('activityType.name')
                    ->label('Type')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('mode')
                    ->label('Mode')
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'take_home',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'individual' => 'Individual',
                        'group' => 'Group',
                        'take_home' => 'Take-Home',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'info' => 'written',
                        'danger' => 'performance',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'written' => 'Written',
                        'performance' => 'Performance',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('format')
                    ->formatStateUsing(fn (string $state, Activity $record): string => 
                        $state === 'other' ? $record->custom_format : ucfirst($state)
                    ),
                Tables\Columns\TextColumn::make('total_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'draft',
                        'success' => 'published',
                        'warning' => 'archived',
                    ]),
                Tables\Columns\TextColumn::make('deadline')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('mode')
                    ->options([
                        'individual' => 'Individual',
                        'group' => 'Group',
                        'take_home' => 'Take-Home',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'written' => 'Written',
                        'performance' => 'Performance',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Activity $record) {
                        $newActivity = $record->replicate();
                        $newActivity->title = "Copy of {$record->title}";
                        $newActivity->status = 'draft';
                        $newActivity->save();
                        
                        // Duplicate roles if it's a group activity
                        if ($record->isGroupActivity()) {
                            foreach ($record->roles as $role) {
                                $newRole = $role->replicate();
                                $newRole->activity_id = $newActivity->id;
                                $newRole->save();
                            }
                        }
                        
                        return redirect()->route('filament.admin.resources.activities.edit', $newActivity);
                    }),
                Tables\Actions\Action::make('track_progress')
                    ->label('Track Progress')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Activity $record) => route('activities.progress', $record))
                    ->visible(fn (Activity $record) => $record->isPublished()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each(function (Activity $record) {
                                $record->update(['status' => 'published']);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GroupsRelationManager::class,
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
