<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Student; // Use Student model
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table; // Useful for quick numeric input
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentSubmissionsRelationManager extends RelationManager
{
    // Change relationship if needed, but we're primarily querying Students
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Student Grades & Submissions';

    // We don't define a form here as we're editing via table actions

    public function isReadOnly(): bool
    {
        return false; // Allow editing via actions
    }

    public function table(Table $table): Table
    {
        /** @var Activity $activity */
        $activity = $this->getOwnerRecord();
        $teamId = Auth::user()->currentTeam->id;
        $totalPoints = $activity->total_points ?? 0; // Get total points for context

        return $table
            ->query(
                // Base query on active students in the current team
                Student::query()
                    ->where('team_id', $teamId)
                    ->where('status', 'active') // Only show active students
            )
            ->recordTitleAttribute('name') // Identify records by student name
            ->columns([
                TextColumn::make('name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Inline Score Input/Display
                TextInputColumn::make('score') // Use TextInputColumn for quick edits
                    ->label('Score')
                    ->rules([
                        'nullable',
                        'numeric',
                        'min:0',
                        "max:{$totalPoints}",
                    ]) // Add validation
                    ->extraAttributes([
                        'style' => 'width: 80px; text-align: center;',
                    ]) // Adjust width
                    // Load the score from the submission
                    ->getStateUsing(
                        fn (Student $record) => $this->getSubmissionForStudent(
                            $record,
                            $activity
                        )?->score
                    )
                    // Update the score via helper method
                    ->updateStateUsing(function (
                        Student $record,
                        ?string $state
                    ) use ($activity): void {
                        $this->updateStudentData($record->id, $activity->id, [
                            'score' => $state,
                        ]);
                    }),

                // Display Max Points
                Tables\Columns\TextColumn::make('max_points')
                    ->label('/ Max')
                    ->formatStateUsing(fn () => '/ '.$totalPoints)
                    ->alignCenter(),

                // Inline Feedback Action
                TextColumn::make('feedback_action') // Use TextColumn
                    ->label('Feedback')
                    ->tooltip('Click to view/edit feedback')
                    ->icon('heroicon-o-chat-bubble-left-right') // Display an icon
                    ->alignCenter() // Center the icon
                    ->color(
                        fn (Student $record) => $this->getSubmissionForStudent(
                            $record,
                            $activity
                        )?->feedback
                            ? 'primary'
                            : 'gray'
                    ) // Keep color logic based on feedback presence
                    ->action(
                        // Attach the action definition here
                        Action::make('edit_feedback') // Action definition remains the same
                            ->label(
                                fn (
                                    Student $record
                                ) => "Feedback for {$record->name}"
                            )
                            ->modalWidth('lg')
                            ->fillForm(
                                fn (Student $record) => [
                                    'feedback' => $this->getSubmissionForStudent(
                                        $record,
                                        $activity
                                    )?->feedback,
                                ]
                            )
                            ->form([
                                Forms\Components\RichEditor::make('feedback')
                                    ->label('Teacher Feedback')
                                    ->disableToolbarButtons(['attachFiles'])
                                    ->nullable(),
                            ])
                            ->action(function (
                                Student $record,
                                array $data
                            ) use ($activity): void {
                                $this->updateStudentData(
                                    $record->id,
                                    $activity->id,
                                    ['feedback' => $data['feedback']]
                                );
                                Notification::make()
                                    ->title('Feedback updated.')
                                    ->success()
                                    ->send();
                            })
                    ),

                BadgeColumn::make('submission_status')
                    ->label('Status')
                    ->getStateUsing(
                        fn (Student $record) => $this->getSubmissionForStudent(
                            $record,
                            $activity
                        )?->status ?? 'not_submitted'
                    )
                    ->colors([
                        'gray' => 'not_submitted',
                        'danger' => 'missing', // Or 'late' if deadline passed
                        'warning' => 'draft',
                        'info' => 'submitted',
                        'success' => 'completed', // Typically after grading
                        // Add other statuses as needed
                    ])
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'not_submitted' => 'Not Submitted',
                            'in_progress' => 'In Progress', // If you use this status
                            default => Str::title(
                                str_replace('_', ' ', $state)
                            ),
                        }
                    ),

                // Optional: View Submission Details Action (if complex submission like form/files)
                TextColumn::make('view_submission') // Use TextColumn
                    ->label('View')
                    ->tooltip('View Full Submission')
                    ->icon('heroicon-o-eye') // Display an icon
                    ->alignCenter() // Center the icon
                    ->color('gray')
                    ->action(
                        // Attach the action definition here
                        Action::make('view_details') // Action definition remains the same
                            ->label(
                                fn (
                                    Student $record
                                ) => "Submission by {$record->name}"
                            )
                            ->modalHeading(
                                fn (
                                    Student $record
                                ) => "Submission Details: {$record->name}"
                            ) // Optional: Add a heading
                            ->modalContent(function (Student $record) use (
                                $activity
                            ) {
                                $submission = $this->getSubmissionForStudent(
                                    $record,
                                    $activity
                                );

                                // Make sure the view path is correct
                                return $submission
                                    ? view(
                                        'filament.resources.activity-resource.partials.submission-details',
                                        ['submission' => $submission]
                                    )
                                    : view()->make(
                                        'filament::components.modal.content',
                                        [
                                            'slot' => '<p class="text-center text-gray-500 dark:text-gray-400">No submission found.</p>',
                                        ]
                                    ); // Use a standard empty state
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ) // End the action definition attached to TextColumn
                    ->visible(function (?Model $record) use ($activity) {
                        // Allow null or base Model
                        // Ensure $record is actually a Student instance before proceeding
                        if (! $record instanceof Student) {
                            return false;
                        }

                        // Now safely check for submission
                        return (bool) $this->getSubmissionForStudent(
                            $record,
                            $activity
                        );
                    }), // Only show if record is a Student and submission exists

                TextColumn::make('graded_details')
                    ->label('Graded')
                    ->getStateUsing(function (Student $record) use ($activity) {
                        $submission = $this->getSubmissionForStudent(
                            $record,
                            $activity
                        );
                        if ($submission && $submission->graded_at) {
                            $grader = $submission->gradedBy?->name ?? 'System'; // gradedBy relationship

                            return "By {$grader} on ".
                                $submission->graded_at->format('M d, Y H:i');
                        }

                        return 'Not Graded';
                    })
                    ->color(
                        fn (string $state) => $state === 'Not Graded'
                            ? 'gray'
                            : null
                    )
                    ->toggleable(isToggledHiddenByDefault: true), // Hide by default

                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('M d, Y H:i')
                    ->getStateUsing(
                        fn (Student $record) => $this->getSubmissionForStudent(
                            $record,
                            $activity
                        )?->submitted_at
                    )
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('submission_status')
                    ->label('Submission Status')
                    ->options([
                        'not_submitted' => 'Not Submitted',
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'completed' => 'Completed',
                        // Add other relevant statuses
                    ])
                    ->query(function (Builder $query, array $data) use (
                        $activity
                    ) {
                        $status = $data['value'] ?? null;
                        if (! $status) {
                            return $query;
                        }

                        if ($status === 'not_submitted') {
                            // Find students who DO NOT have a submission for this activity
                            return $query->whereDoesntHave(
                                'submissions',
                                fn ($subQuery) => $subQuery->where(
                                    'activity_id',
                                    $activity->id
                                )
                            );
                        } else {
                            // Find students who HAVE a submission with the specified status
                            return $query->whereHas(
                                'submissions',
                                fn ($subQuery) => $subQuery
                                    ->where('activity_id', $activity->id)
                                    ->where('status', $status)
                            );
                        }
                    }),
                Tables\Filters\TernaryFilter::make('is_graded')
                    ->label('Grading Status')
                    ->placeholder('All Students')
                    ->trueLabel('Graded')
                    ->falseLabel('Not Graded')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas(
                            'submissions',
                            fn ($sq) => $sq
                                ->where('activity_id', $activity->id)
                                ->whereNotNull('graded_at')
                        ),
                        false: fn (Builder $query) => $query->where(function (
                            $q
                        ) use ($activity): void {
                            $q->whereDoesntHave(
                                'submissions',
                                fn ($sq) => $sq->where(
                                    'activity_id',
                                    $activity->id
                                )
                            ) // Not submitted counts as not graded
                                ->orWhereHas(
                                    'submissions',
                                    fn ($sq) => $sq
                                        ->where('activity_id', $activity->id)
                                        ->whereNull('graded_at')
                                );
                        })
                    ),
                // Add group filter if applicable (requires reading query param or state)
                // This is more complex, might need Livewire lifecycle hooks
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_grade')
                    ->label('Bulk Grade')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\Select::make('students')
                            ->label('Select Students to Grade')
                            ->options(
                                // Provide options from the current table query results
                                fn () => Student::where('team_id', $teamId)
                                    ->where('status', 'active')
                                    ->pluck('name', 'id')
                            )
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->helperText(
                                'Select students to apply the same score/feedback.'
                            ),
                        TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($totalPoints)
                            ->required(),
                        Forms\Components\RichEditor::make('feedback')
                            ->label('Feedback (Optional)')
                            ->disableToolbarButtons(['attachFiles'])
                            ->nullable(),
                        Select::make('status') // Allow setting status during bulk grade
                            ->label('Set Status To')
                            ->options([
                                'submitted' => 'Submitted',
                                'completed' => 'Completed',
                                // 'late' => 'Late', // Add if needed
                            ])
                            ->default('completed')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($activity): void {
                        $studentIds = $data['students'] ?? [];
                        $count = count($studentIds);

                        if ($count === 0) {
                            Notification::make()
                                ->title('No students selected.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $updateData = [
                            'score' => $data['score'],
                            'feedback' => $data['feedback'] ?? null,
                            'status' => $data['status'] ?? 'completed', // Default to completed when grading
                        ];

                        foreach ($studentIds as $studentId) {
                            $this->updateStudentData(
                                $studentId,
                                $activity->id,
                                $updateData
                            );
                        }

                        Notification::make()
                            ->title('Bulk Grading Complete')
                            ->body(
                                "Successfully graded {$count} student submissions."
                            )
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                // Edit action to modify score/feedback/status in a modal
                Tables\Actions\Action::make('edit_grade')
                    ->label('Grade/Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->modalWidth('lg')
                    ->fillForm(function (Student $record) use ($activity) {
                        $submission = $this->getSubmissionForStudent(
                            $record,
                            $activity
                        );

                        return [
                            'score' => $submission?->score,
                            'feedback' => $submission?->feedback,
                            'status' => $submission?->status ?? 'submitted', // Default if no submission yet
                        ];
                    })
                    ->form([
                        TextInput::make('score')
                            ->label("Score (Max: {$totalPoints})")
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($totalPoints)
                            ->required(), // Make score required when editing
                        Forms\Components\RichEditor::make('feedback')
                            ->label('Teacher Feedback')
                            ->disableToolbarButtons(['attachFiles'])
                            ->nullable(),
                        Select::make('status')
                            ->label('Submission Status')
                            ->options([
                                'submitted' => 'Submitted',
                                'completed' => 'Completed',
                                'late' => 'Late',
                                'draft' => 'Draft', // Allow reverting?
                                'missing' => 'Missing',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, Student $record) use (
                        $activity
                    ): void {
                        $this->updateStudentData(
                            $record->id,
                            $activity->id,
                            $data
                        );
                        Notification::make()
                            ->title("Grade updated for {$record->name}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_update_grades')
                        ->label('Grade Selected Students')
                        ->icon('heroicon-o-check-circle')
                        ->color('primary')
                        ->modalWidth('lg')
                        ->form([
                            TextInput::make('score')
                                ->label("Score (Max: {$totalPoints})")
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($totalPoints)
                                ->required(),
                            Forms\Components\RichEditor::make('feedback')
                                ->label('Feedback (Optional)')
                                ->disableToolbarButtons(['attachFiles'])
                                ->nullable(),
                            Select::make('status')
                                ->label('Set Status To')
                                ->options([
                                    'submitted' => 'Submitted',
                                    'completed' => 'Completed',
                                ])
                                ->default('completed')
                                ->required(),
                        ])
                        ->action(function (
                            Collection $records,
                            array $data
                        ) use ($activity): void {
                            $updateData = [
                                'score' => $data['score'],
                                'feedback' => $data['feedback'] ?? null,
                                'status' => $data['status'] ?? 'completed',
                            ];
                            $count = 0;
                            $records->each(function (Student $record) use (
                                $activity,
                                $updateData,
                                &$count
                            ): void {
                                $this->updateStudentData(
                                    $record->id,
                                    $activity->id,
                                    $updateData
                                );
                                $count++;
                            });

                            Notification::make()
                                ->title('Bulk Grading Complete')
                                ->body(
                                    "Successfully graded {$count} selected student submissions."
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped()
            ->paginated(false); // Show all students at once for grading
    }

    /**
     * Helper to get the submission record for a given student and activity.
     */
    protected function getSubmissionForStudent(
        Student $student,
        Activity $activity
    ): ?ActivitySubmission {
        // Eager load submission if possible or cache result?
        // For simplicity, query directly here. Optimize if performance becomes an issue.
        return ActivitySubmission::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();
    }

    /**
     * Centralized helper to update or create submission data for a student.
     * Takes an array of data to update.
     */
    protected function updateStudentData(
        string $studentId,
        string $activityId,
        array $data
    ): void {
        try {
            /** @var ActivitySubmission $submission */
            $submission = ActivitySubmission::firstOrNew(
                ['activity_id' => $activityId, 'student_id' => $studentId],
                ['team_id' => Auth::user()->currentTeam->id] // Set team_id on creation
            );

            // Track if score is being set/updated
            $isGrading = isset($data['score']) && $data['score'] !== null;

            // Apply updates from $data array
            if (array_key_exists('score', $data)) {
                $submission->score = $data['score'];
            }
            if (array_key_exists('feedback', $data)) {
                $submission->feedback = $data['feedback'];
            }
            if (array_key_exists('final_grade', $data)) {
                // Handle final_grade if passed
                $submission->final_grade = $data['final_grade'];
            }
            if (array_key_exists('status', $data)) {
                $submission->status = $data['status'];
            }

            // Set timestamps and grader if grading occurred
            if ($isGrading) {
                $submission->graded_by = Auth::id();
                $submission->graded_at = now();
                // Often, when grading, you mark it 'completed'
                if (! isset($data['status'])) {
                    // Only default if status wasn't explicitly set
                    $submission->status = 'completed';
                }
            }

            // Set submission time if this is the first save
            if (! $submission->exists) {
                $submission->submitted_at = now();
                // Default status if not provided and not grading
                if (! isset($data['status']) && ! $isGrading) {
                    $submission->status = 'submitted'; // Default to submitted on first save
                }
            }

            $submission->save();

            Log::info('StudentSubmissionsRelationManager - Data Updated', [
                'submission_id' => $submission->id,
                'student_id' => $studentId,
                'activity_id' => $activityId,
                'updated_data' => array_keys($data),
            ]);
        } catch (\Exception $e) {
            Log::error(
                'StudentSubmissionsRelationManager - Error Updating Data',
                [
                    'student_id' => $studentId,
                    'activity_id' => $activityId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            Notification::make()
                ->title('Error Saving Grade')
                ->body('An unexpected error occurred. Please try again.')
                ->danger()
                ->send();
            // Optionally re-throw or handle differently
            // throw $e;
        }
    }

    // Keep old methods for compatibility if called elsewhere, but delegate to new one
    protected function updateStudentScore(
        string $studentId,
        string $activityId,
        ?string $score,
        ?string $feedback = null,
        ?float $finalGrade = null,
        ?string $status = null
    ): void {
        $data = array_filter(
            [
                'score' => $score,
                'feedback' => $feedback,
                'final_grade' => $finalGrade,
                'status' => $status ?? ($score !== null ? 'completed' : null), // Default status if scoring
            ],
            fn ($value) => $value !== null
        ); // Only include non-null values
        $this->updateStudentData($studentId, $activityId, $data);
    }

    protected function updateStudentFeedback(
        string $studentId,
        string $activityId,
        ?string $feedback
    ): void {
        $this->updateStudentData($studentId, $activityId, [
            'feedback' => $feedback,
        ]);
    }
}
