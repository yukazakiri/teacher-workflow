<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Models\Student;
use App\Models\ActivitySubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class StudentSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label('Student')
                    ->options(function () {
                        return Student::where('team_id', Auth::user()->currentTeam->id)
                            ->where('status', 'active')
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),

                Hidden::make('activity_id')
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
        $activity = $this->getOwnerRecord();
        $teamId = Auth::user()->currentTeam->id;

        return $table
            ->query(
                // Use Student model as the base query instead of submissions
                Student::query()
                    ->where('team_id', $teamId)
                    ->where('status', 'active')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->getStateUsing(function (Student $record) use ($activity) {
                        $submission = ActivitySubmission::where('activity_id', $activity->id)
                            ->where('student_id', $record->id)
                            ->first();

                        return $submission ? $submission->score : null;
                    })
                    ->numeric()
                    ->alignCenter()
                    ->action(function (Student $record, $state) use ($activity) {
                        return Tables\Actions\Action::make('updateScore')
                            ->form([
                                TextInput::make('score')
                                    ->label('Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default($state)
                                    ->required(),
                            ])
                            ->action(function (array $data) use ($record, $activity) {
                                $this->updateStudentScore($record->id, $activity->id, $data['score']);

                                Notification::make()
                                    ->title('Score updated')
                                    ->success()
                                    ->send();
                            });
                    }),

                Tables\Columns\TextColumn::make('feedback')
                    ->label('Feedback')
                    ->getStateUsing(function (Student $record) use ($activity) {
                        $submission = ActivitySubmission::where('activity_id', $activity->id)
                            ->where('student_id', $record->id)
                            ->first();

                        return $submission ? $submission->feedback : null;
                    })
                    ->words(10)
                    ->action(function (Student $record, $state) use ($activity) {
                        return Tables\Actions\Action::make('updateFeedback')
                            ->form([
                                Textarea::make('feedback')
                                    ->label('Feedback')
                                    ->default($state)
                                    ->maxLength(65535),
                            ])
                            ->action(function (array $data) use ($record, $activity) {
                                $this->updateStudentFeedback($record->id, $activity->id, $data['feedback']);

                                Notification::make()
                                    ->title('Feedback updated')
                                    ->success()
                                    ->send();
                            });
                    }),

                BadgeColumn::make('submission_status')
                    ->label('Status')
                    ->getStateUsing(function (Student $record) use ($activity) {
                        $submission = ActivitySubmission::where('activity_id', $activity->id)
                            ->where('student_id', $record->id)
                            ->first();

                        return $submission ? $submission->status : 'not_submitted';
                    })
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'in_progress',
                        'success' => 'submitted',
                        'danger' => 'late',
                        'primary' => 'completed',
                        'gray' => 'not_submitted',
                    ]),

                TextColumn::make('graded_by')
                    ->label('Graded By')
                    ->getStateUsing(function (Student $record) use ($activity) {
                        $submission = ActivitySubmission::where('activity_id', $activity->id)
                            ->where('student_id', $record->id)
                            ->first();

                        if ($submission && $submission->graded_by) {
                            $user = \App\Models\User::find($submission->graded_by);
                            return $user ? $user->name : 'Unknown';
                        }

                        return 'Not graded';
                    }),

                TextColumn::make('graded_at')
                    ->label('Graded At')
                    ->dateTime()
                    ->getStateUsing(function (Student $record) use ($activity) {
                        $submission = ActivitySubmission::where('activity_id', $activity->id)
                            ->where('student_id', $record->id)
                            ->first();

                        return $submission ? $submission->graded_at : null;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Submission Status')
                    ->options([
                        'not_submitted' => 'Not Submitted',
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'submitted' => 'Submitted',
                        'late' => 'Late',
                        'completed' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data) use ($activity) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'not_submitted') {
                            return $query->whereNotIn('id', function ($subQuery) use ($activity) {
                                $subQuery->select('student_id')
                                    ->from('activity_submissions')
                                    ->where('activity_id', $activity->id);
                            });
                        }

                        return $query->whereIn('id', function ($subQuery) use ($activity, $data) {
                            $subQuery->select('student_id')
                                ->from('activity_submissions')
                                ->where('activity_id', $activity->id)
                                ->where('status', $data['value']);
                        });
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_grade')
                    ->label('Bulk Grade Students')
                    ->icon('heroicon-o-academic-cap')
                    ->form([
                        Select::make('students')
                            ->label('Select Students')
                            ->options(function () use ($teamId) {
                                return Student::where('team_id', $teamId)
                                    ->where('status', 'active')
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->required(),

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
                    ->action(function (array $data) {
                        $activity = $this->getOwnerRecord();
                        $studentsCount = count($data['students']);

                        // Log the data for debugging
                        Log::info('StudentSubmissionsRelationManager - Bulk Grade Action', [
                            'activity_id' => $activity->id,
                            'student_count' => $studentsCount,
                            'students' => $data['students'],
                        ]);

                        foreach ($data['students'] as $studentId) {
                            $this->updateStudentScore($studentId, $activity->id, $data['score'], $data['feedback'], $data['final_grade']);
                        }

                        Notification::make()
                            ->title('Submissions Graded')
                            ->body("Successfully graded {$studentsCount} student submissions.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_submission')
                    ->label('Edit Submission')
                    ->icon('heroicon-o-pencil')
                    ->form(function (Student $record) {
                        $activity = $this->getOwnerRecord();
                        $submission = ActivitySubmission::where('activity_id', $activity->id)
                            ->where('student_id', $record->id)
                            ->first();

                        return [
                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->default($submission ? $submission->score : null),

                            TextInput::make('final_grade')
                                ->label('Final Grade (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->default($submission ? $submission->final_grade : null),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'in_progress' => 'In Progress',
                                    'submitted' => 'Submitted',
                                    'late' => 'Late',
                                    'completed' => 'Completed',
                                ])
                                ->default($submission ? $submission->status : 'submitted'),

                            Textarea::make('feedback')
                                ->label('Feedback')
                                ->maxLength(65535)
                                ->default($submission ? $submission->feedback : null),
                        ];
                    })
                    ->action(function (array $data, Student $record) {
                        $activity = $this->getOwnerRecord();
                        $this->updateStudentScore(
                            $record->id,
                            $activity->id,
                            $data['score'],
                            $data['feedback'],
                            $data['final_grade'],
                            $data['status']
                        );

                        Notification::make()
                            ->title('Submission Updated')
                            ->body("Updated submission for {$record->name}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
                            $activity = $this->getOwnerRecord();
                            $count = 0;

                            $records->each(function (Student $record) use ($activity, $data, &$count) {
                                $this->updateStudentScore(
                                    $record->id,
                                    $activity->id,
                                    $data['score'],
                                    $data['feedback'],
                                    $data['final_grade']
                                );
                                $count++;
                            });

                            Notification::make()
                                ->title('Submissions Graded')
                                ->body("Successfully graded {$count} student submissions.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->striped()
            ->paginated(false);
    }

    /**
     * Update a student's score for an activity
     */
    protected function updateStudentScore(
        string $studentId,
        string $activityId,
        ?string $score,
        ?string $feedback = null,
        ?float $finalGrade = null,
        string $status = 'completed'
    ): void {
        try {
            // Log for debugging
            Log::info('StudentSubmissionsRelationManager - Updating Score', [
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'score' => $score,
                'feedback' => $feedback,
                'final_grade' => $finalGrade,
                'status' => $status,
            ]);

            $submission = ActivitySubmission::firstOrNew([
                'activity_id' => $activityId,
                'student_id' => $studentId,
            ]);

            $submission->score = $score;

            if ($feedback !== null) {
                $submission->feedback = $feedback;
            }

            if ($finalGrade !== null) {
                $submission->final_grade = $finalGrade;
            }

            if (!$submission->exists) {
                $submission->status = $status;
                $submission->submitted_at = now();
            }

            // If we're setting a score, mark as graded
            if ($score !== null) {
                $submission->graded_by = Auth::id();
                $submission->graded_at = now();
                $submission->status = $status;
            }

            $submission->save();

            Log::info('StudentSubmissionsRelationManager - Score Updated Successfully', [
                'submission_id' => $submission->id,
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'score' => $score,
            ]);
        } catch (\Exception $e) {
            Log::error('StudentSubmissionsRelationManager - Error Updating Score', [
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update a student's feedback for an activity
     */
    protected function updateStudentFeedback(string $studentId, string $activityId, ?string $feedback): void
    {
        try {
            // Log for debugging
            Log::info('StudentSubmissionsRelationManager - Updating Feedback', [
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'feedback' => $feedback,
            ]);

            $submission = ActivitySubmission::firstOrNew([
                'activity_id' => $activityId,
                'student_id' => $studentId,
            ]);

            $submission->feedback = $feedback;

            if (!$submission->exists) {
                $submission->status = 'submitted';
                $submission->submitted_at = now();
            }

            $submission->save();

            Log::info('StudentSubmissionsRelationManager - Feedback Updated Successfully', [
                'submission_id' => $submission->id,
                'student_id' => $studentId,
                'activity_id' => $activityId,
            ]);
        } catch (\Exception $e) {
            Log::error('StudentSubmissionsRelationManager - Error Updating Feedback', [
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
