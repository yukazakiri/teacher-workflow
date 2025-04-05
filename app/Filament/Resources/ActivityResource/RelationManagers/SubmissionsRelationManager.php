<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Models\ActivitySubmission;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make('Student Information')
                            ->schema([
                                Select::make('student_id')
                                    ->label('Student')
                                    ->relationship('student', 'name')
                                    ->options(
                                        User::whereHas('teams', function (Builder $query) {
                                            $query->where('team_id', Auth::user()->currentTeam->id);
                                        })->where('id', '!=', Auth::id())->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null),

                                Select::make('group_id')
                                    ->label('Group')
                                    ->relationship('group', 'name')
                                    ->options(fn () => $this->getOwnerRecord()->groups->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn () => $this->getOwnerRecord()->mode === 'group'),
                            ]),

                        Section::make('Submission Details')
                            ->schema([
                                RichEditor::make('content')
                                    ->label('Submission Content')
                                    ->placeholder('Enter submission content')
                                    ->columnSpan(2),

                                FileUpload::make('attachments')
                                    ->label('Attachments')
                                    ->multiple()
                                    ->directory('activity-submissions')
                                    ->preserveFilenames()
                                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->maxSize(10240) // 10MB
                                    ->columnSpan(2),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'submitted' => 'Submitted',
                                        'completed' => 'Completed',
                                        'late' => 'Late',
                                    ])
                                    ->default('submitted')
                                    ->required(),

                                DateTimePicker::make('submitted_at')
                                    ->label('Submission Date')
                                    ->default(now())
                                    ->required(),
                            ])
                            ->columns(2),

                        Section::make('Grading')
                            ->schema([
                                TextInput::make('score')
                                    ->label('Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(fn () => $this->getOwnerRecord()->total_points)
                                    ->suffix(fn () => '/ '.$this->getOwnerRecord()->total_points),

                                RichEditor::make('feedback')
                                    ->label('Feedback')
                                    ->placeholder('Enter feedback for the student')
                                    ->columnSpan(2),

                                DateTimePicker::make('graded_at')
                                    ->label('Graded Date')
                                    ->default(now()),

                                Select::make('graded_by')
                                    ->label('Graded By')
                                    ->relationship('gradedBy', 'name')
                                    ->default(Auth::id())
                                    ->disabled(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => $this->getOwnerRecord()->mode === 'group'),

                BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'draft',
                        'warning' => 'late',
                        'primary' => 'submitted',
                        'success' => 'completed',
                    ]),

                TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state, ActivitySubmission $record) => $state !== null ? "{$state} / {$this->getOwnerRecord()->total_points}" : 'Not graded'
                    )
                    ->sortable(),

                IconColumn::make('has_attachments')
                    ->label('Attachments')
                    ->boolean()
                    ->getStateUsing(fn (ActivitySubmission $record): bool => $record->attachments !== null && count($record->attachments) > 0
                    ),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('graded_at')
                    ->label('Graded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'completed' => 'Completed',
                        'late' => 'Late',
                    ]),

                Filter::make('graded')
                    ->label('Graded Status')
                    ->query(fn (Builder $query, array $data): Builder => $data['isActive'] ? $query->whereNotNull('graded_at') : $query->whereNull('graded_at')
                    )
                    ->toggle(),

                SelectFilter::make('group_id')
                    ->label('Group')
                    ->options(fn () => $this->getOwnerRecord()->groups->pluck('name', 'id'))
                    ->visible(fn () => $this->getOwnerRecord()->mode === 'group'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),

                Action::make('export_grades')
                    ->label('Export Grades')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->action(function () {
                        return redirect()->route('activities.generate-report', [
                            'activity' => $this->getOwnerRecord(),
                            'format' => 'csv',
                        ]);
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('grade')
                    ->label('Grade')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn () => $this->getOwnerRecord()->total_points)
                            ->suffix(fn () => '/ '.$this->getOwnerRecord()->total_points)
                            ->required(),

                        RichEditor::make('feedback')
                            ->label('Feedback')
                            ->placeholder('Enter feedback for the student'),
                    ])
                    ->action(function (ActivitySubmission $record, array $data): void {
                        $record->update([
                            'score' => $data['score'],
                            'feedback' => $data['feedback'],
                            'status' => 'completed',
                            'graded_by' => Auth::id(),
                            'graded_at' => now(),
                        ]);
                    }),

                Action::make('download_attachments')
                    ->label('Download Attachments')
                    ->icon('heroicon-o-download')
                    ->action(function (ActivitySubmission $record): void {
                        // This would typically create a zip file with all attachments
                        // and return a download response
                    })
                    ->visible(fn (ActivitySubmission $record): bool => $record->attachments !== null && count($record->attachments) > 0
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_grade')
                        ->label('Bulk Grade')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(fn () => $this->getOwnerRecord()->total_points)
                                ->suffix(fn () => '/ '.$this->getOwnerRecord()->total_points)
                                ->required(),

                            RichEditor::make('feedback')
                                ->label('Feedback')
                                ->placeholder('Enter feedback for all selected submissions'),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $records->each(function (ActivitySubmission $record) use ($data): void {
                                $record->update([
                                    'score' => $data['score'],
                                    'feedback' => $data['feedback'],
                                    'status' => 'completed',
                                    'graded_by' => Auth::id(),
                                    'graded_at' => now(),
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
