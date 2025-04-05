<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityResource\Pages;
use App\Filament\Admin\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Learning Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('team_id')
                            ->relationship('team', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('activity_type_id')
                            ->relationship('activityType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Activity Details')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('instructions')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('format')
                            ->options([
                                'standard' => 'Standard',
                                'essay' => 'Essay',
                                'presentation' => 'Presentation',
                                'project' => 'Project',
                                'custom' => 'Custom',
                            ])
                            ->reactive()
                            ->required(),
                        Forms\Components\TextInput::make('custom_format')
                            ->visible(fn (callable $get) => $get('format') === 'custom')
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->options([
                                'written' => 'Written',
                                'performance' => 'Performance',
                            ])
                            ->required(),
                        Forms\Components\Select::make('mode')
                            ->options([
                                'individual' => 'Individual',
                                'group' => 'Group',
                                'take_home' => 'Take Home',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('total_points')
                            ->required()
                            ->numeric()
                            ->default(100),
                        Forms\Components\DateTimePicker::make('deadline'),
                    ])->columns(2),

                Forms\Components\Section::make('Submission Settings')
                    ->schema([
                        Forms\Components\Select::make('submission_type')
                            ->options([
                                'form' => 'Form',
                                'resource' => 'Resource',
                                'manual' => 'Manual',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('allow_file_uploads')
                            ->default(false)
                            ->reactive(),
                        Forms\Components\TagsInput::make('allowed_file_types')
                            ->placeholder('Add file types (e.g., pdf, docx)')
                            ->visible(fn (callable $get) => $get('allow_file_uploads')),
                        Forms\Components\TextInput::make('max_file_size')
                            ->numeric()
                            ->default(10)
                            ->suffix('MB')
                            ->visible(fn (callable $get) => $get('allow_file_uploads')),
                        Forms\Components\Toggle::make('allow_teacher_submission')
                            ->default(false)
                            ->helperText('Allow teachers to submit on behalf of students'),
                    ])->columns(2),

                Forms\Components\Section::make('Form Structure')
                    ->schema([
                        Forms\Components\Textarea::make('form_structure')
                            ->visible(fn (callable $get) => $get('submission_type') === 'form')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('team.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('activityType.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'danger' => 'draft',
                        'success' => 'published',
                        'warning' => 'archived',
                    ]),
                Tables\Columns\TextColumn::make('mode')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        'take_home' => 'Take Home',
                    ]),
                Tables\Filters\SelectFilter::make('teacher')
                    ->relationship('teacher', 'name'),
                Tables\Filters\SelectFilter::make('team')
                    ->relationship('team', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('changeStatus')
                        ->label('Change Status')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                    'archived' => 'Archived',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GroupsRelationManager::class,
            RelationManagers\RolesRelationManager::class,
            RelationManagers\SubmissionsRelationManager::class,
            RelationManagers\ResourcesRelationManager::class,
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'published')->count();
    }
}
