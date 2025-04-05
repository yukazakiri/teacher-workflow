<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Activity;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActivitySubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'activitySubmissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('activity_id')
                    ->label('Activity')
                    ->options(function () {
                        return Activity::where('team_id', Auth::user()->currentTeam->id)
                            ->pluck('title', 'id');
                    })
                    ->required()
                    ->searchable(),

                Hidden::make('student_id')
                    ->default(fn ($livewire) => $livewire->getOwnerRecord()->id),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'submitted' => 'Submitted',
                        'late' => 'Late',
                        'completed' => 'Completed',
                    ])
                    ->default('submitted')
                    ->required(),

                TextInput::make('score')
                    ->label('Score')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),

                TextInput::make('final_grade')
                    ->label('Final Grade')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%'),

                Textarea::make('feedback')
                    ->label('Teacher Feedback')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                DateTimePicker::make('submitted_at')
                    ->label('Submission Date')
                    ->default(now()),

                FileUpload::make('attachments')
                    ->label('Attachments')
                    ->multiple()
                    ->directory('submissions')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('activity.title')
                    ->label('Activity')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'in_progress',
                        'success' => 'submitted',
                        'danger' => 'late',
                        'primary' => 'completed',
                    ]),

                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('final_grade')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('gradedBy.name')
                    ->label('Graded By')
                    ->default('Not graded'),

                // TextColumn::make('graded_at')
                //     ->label('Graded At')
                //     ->dateTime()
                //     ->default('Not graded')
                //     ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'submitted' => 'Submitted',
                        'late' => 'Late',
                        'completed' => 'Completed',
                    ]),

                Tables\Filters\Filter::make('graded')
                    ->label('Graded')
                    ->query(fn (Builder $query) => $query->whereNotNull('graded_at')),

                Tables\Filters\Filter::make('not_graded')
                    ->label('Not Graded')
                    ->query(fn (Builder $query) => $query->whereNull('graded_at')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Set the graded_by and graded_at if score is provided
                        if (isset($data['score']) && $data['score'] !== null) {
                            $data['graded_by'] = Auth::id();
                            $data['graded_at'] = now();
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Set the graded_by and graded_at if score is provided
                        if (isset($data['score']) && $data['score'] !== null) {
                            $data['graded_by'] = Auth::id();
                            $data['graded_at'] = now();
                        }

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('grade_submissions')
                        ->label('Grade Submissions')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->required(),

                            TextInput::make('final_grade')
                                ->label('Final Grade (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->required(),

                            Textarea::make('feedback')
                                ->label('Feedback')
                                ->maxLength(65535),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'score' => $data['score'],
                                    'final_grade' => $data['final_grade'],
                                    'feedback' => $data['feedback'],
                                    'status' => 'completed',
                                    'graded_by' => Auth::id(),
                                    'graded_at' => now(),
                                ]);
                            });
                        }),
                ]),
            ]);
    }
}
