<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload; // If using Spatie Media Library
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // For file attachments

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected static string $view = 'filament.resources.activity-resource.pages.view-activity'; // Optional custom view

    protected function getHeaderActions(): array
    {
        $activity = $this->getRecord();
        $user = Auth::user();

        $actions = [
            // Standard Edit action for teachers
            Action::make('edit')
                ->label('Edit Activity')
                ->url(ActivityResource::getUrl('edit', ['record' => $activity]))
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $user->can('update', $activity)), // Check policy
        ];

        // Submission action for students
        if ($user->hasTeamRole($user->currentTeam, 'student') && $activity->isPublished()) {
            // Check if student has already submitted
            $existingSubmission = ActivitySubmission::where('activity_id', $activity->id)
                ->where('student_id', $user->id)
                ->first();

            if ($existingSubmission && ($existingSubmission->isSubmitted() || $existingSubmission->isCompleted())) {
                // TODO: Add logic for resubmission if allowed by activity settings
                // For now, just show a disabled button or a "View Submission" button
                $actions[] = Action::make('view_submission')
                    ->label('View Your Submission')
                    // ->url(...) // Link to view their submission details page or modal
                    ->color('gray')
                    ->disabled() // Or implement view functionality
                    ->icon('heroicon-o-document-check');
            } else {
                $actions[] = Action::make('submit_work')
                    ->label('Submit Work')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->action(null) // Action handled by modal form
                    ->modalWidth('xl')
                    ->modalHeading("Submit Work for: {$activity->title}")
                    ->modalSubmitActionLabel('Submit')
                    ->form(fn (Form $form) => $this->buildSubmissionForm($form, $activity))
                    ->action(function (array $data) use ($activity, $user, $existingSubmission): void {
                        $this->processSubmission($data, $activity, $user, $existingSubmission);
                    })
                    ->visible(function () use ($activity): bool {
                        // Add conditions like deadline checks, allow_late_submissions etc.
                        return $activity->isPublished() &&
                               ( $activity->due_date === null ||
                                 now()->lessThanOrEqualTo($activity->due_date) ||
                                 $activity->allow_late_submissions
                               );
                    });
            }
        }

        return $actions;
    }

    protected function buildSubmissionForm(Form $form, Activity $activity): Form
    {
        $components = [];
        $user = Auth::user();

        // 1. Text Entry (if allowed)
        if ($activity->allow_text_entry) {
            $components[] = Section::make('Your Response')
                ->collapsible()
                ->schema([
                    RichEditor::make('content')
                        ->label('Type your response here')
                        ->helperText('Provide your answer or thoughts for this activity.')
                        ->required(function () use ($activity) {
                            // Required if no file upload and no form fields are primary
                            return ! $activity->allow_file_uploads && $activity->submission_type !== 'form';
                        })
                        ->disableToolbarButtons(['attachFiles']) // Student shouldn't attach files here
                        ->columnSpanFull(),
                ]);
        }

        // 2. File Uploads (if allowed)
        if ($activity->allowsFileUploads()) {
            $components[] = Section::make('Attach Files')
                ->collapsible()
                ->schema([
                    FileUpload::make('attachments')
                        ->label('Upload your file(s)')
                        ->multiple()
                        ->directory("activity-submissions/{$activity->id}/{$user->id}") // Store files in a structured path
                        ->preserveFilenames()
                        ->maxSize($activity->max_file_size ?: 5120) // Default 5MB if not set
                        ->acceptedFileTypes($activity->allowed_file_types ?: []) // Use configured types or allow any
                        ->helperText($activity->allowed_file_types
                            ? 'Allowed file types: '.implode(', ', $activity->allowed_file_types).
                              ($activity->max_file_size ? '. Max size: '.($activity->max_file_size / 1024).'MB' : '')
                            : ($activity->max_file_size ? 'Max size: '.($activity->max_file_size / 1024).'MB' : 'No specific file types required.')
                        )
                        ->required(function () use ($activity) {
                            // Required if no text entry and no form fields are primary
                            return ! $activity->allow_text_entry && $activity->submission_type !== 'form';
                        })
                        ->columnSpanFull(),
                ]);
        }

        // 3. Form Builder (if submission_type is 'form' and form_config exists)
        if ($activity->isFormActivity() && ! empty($activity->form_config)) {
            $formBuilderFields = [];
            try {
                // Assuming ActivityResource has a method to convert form_config to Filament fields
                // This might need to be a static method or a service.
                // For now, let's imagine such a method exists.
                // $formBuilderFields = ActivityResource::generateFieldsFromConfig($activity->form_config);
                // For a simple example, let's create a placeholder:
                // You'll need to implement the logic to parse $activity->form_config
                // and convert it into Filament Form Components.
                foreach ($activity->form_config as $fieldConfig) {
                    // This is a simplified example. You'll need robust parsing.
                    $type = $fieldConfig['type'] ?? 'textInput';
                    $data = $fieldConfig['data'] ?? [];
                    $name = $data['name'] ?? 'field_'.uniqid();
                    $label = $data['label'] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
                    $isRequired = $data['required'] ?? false;

                    // Map to Filament components based on $type
                    // Placeholder for actual mapping
                    $formBuilderFields[] = \Filament\Forms\Components\TextInput::make("form_responses.{$name}")
                        ->label($label)
                        ->required($isRequired)
                         ->helperText($data['helperText'] ?? null);
                }

                 $components[] = Section::make('Complete the Form')
                    ->description('Please fill out all required fields below.')
                    ->collapsible()
                    ->schema($formBuilderFields);

            } catch (\Exception $e) {
                Log::error("Error generating form from config for activity {$activity->id}: ".$e->getMessage());
                $components[] = TextEntry::make('form_error')
                                ->label('')
                                ->default('Could not load the submission form. Please contact your teacher.');
            }
        }

        // Ensure at least one submission method is available
        if (empty($components)) {
            // This case should ideally be prevented by activity creation validation
             $components[] = \Filament\Forms\Components\Placeholder::make('no_submission_method')
                ->label('')
                ->content('This activity does not have a configured submission method. Please contact your teacher.');
        }


        return $form->schema($components);
    }

    protected function processSubmission(array $data, Activity $activity, User $user, ?ActivitySubmission $existingSubmission): void
    {
        try {
            $submissionData = [
                'student_id' => $user->id,
                'activity_id' => $activity->id,
                'team_id' => $user->currentTeam->id, // Ensure team_id is set
                'content' => $data['content'] ?? null,
                'attachments' => $data['attachments'] ?? null, // Filament handles file paths
                'form_responses' => $data['form_responses'] ?? null,
                'status' => 'submitted', // Or 'draft' if save as draft is implemented
                'submitted_at' => now(),
                'submitted_by_teacher' => false, // Student submission
            ];

            if ($existingSubmission) {
                // Update existing draft or resubmission
                $existingSubmission->update($submissionData);
                $submission = $existingSubmission;
                 Notification::make()
                    ->title('Submission Updated')
                    ->body('Your work has been successfully updated.')
                    ->success()
                    ->send();
            } else {
                $submission = ActivitySubmission::create($submissionData);
                 Notification::make()
                    ->title('Submission Successful')
                    ->body('Your work has been submitted.')
                    ->success()
                    ->send();
            }

            Log::info("Student {$user->id} submitted to activity {$activity->id}. Submission ID: {$submission->id}");

            // Potentially redirect or refresh part of the page
            // $this->dispatch('refresh'); // If you need to refresh data on the page

        } catch (\Exception $e) {
            Log::error("Error processing submission for activity {$activity->id} by user {$user->id}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            Notification::make()
                ->title('Submission Failed')
                ->body('An error occurred while submitting your work. Please try again or contact your teacher.')
                ->danger()
                ->send();
        }
    }


    public function infolist(Infolist $infolist): Infolist
    {
        $activity = $this->getRecord();
        return $infolist
            ->record($activity)
            ->schema([
                InfolistSection::make('Activity Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Activity Title')
                            ->columnSpan(2)
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'published' => 'success',
                                'archived' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('activityType.name')
                            ->label('Type'),
                        TextEntry::make('category')
                            ->label('Category')
                            ->formatStateUsing(fn (string $state) => ucfirst($state)),
                        TextEntry::make('mode')
                            ->label('Mode')
                            ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),

                        Grid::make(1)->schema([
                            TextEntry::make('description')
                                ->label('Description')
                                ->html()
                                ->columnSpanFull(),
                            TextEntry::make('instructions')
                                ->label('Instructions')
                                ->html()
                                ->columnSpanFull()
                                ->visible((bool) $activity->instructions),
                        ])->columnSpan(3),
                    ]),

                InfolistSection::make('Grading Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_points')
                            ->label('Total Points')
                            ->numeric(),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->dateTime('M d, Y H:i A')
                            ->placeholder('No due date'),
                        TextEntry::make('allow_late_submissions')
                            ->label('Late Submissions')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Allowed' : 'Not Allowed')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('component_type_description')
                            ->label('SHS Component')
                            ->visible(fn() => $activity->team?->grading_system_type === \App\Models\Team::GRADING_SYSTEM_SHS),
                        TextEntry::make('term_description')
                            ->label('College Term')
                            ->visible(fn() => $activity->team?->usesCollegeTermGrading()),
                         TextEntry::make('credit_units')
                            ->label('Credit Units (for GWA)')
                            ->numeric()
                            ->visible(fn() => $activity->team?->usesCollegeGwaGrading()),
                    ]),

                InfolistSection::make('Submission Settings')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('submission_type')
                             ->label('Submission Type')
                             ->formatStateUsing(fn (string $state) => ucfirst($state)),
                        TextEntry::make('allow_text_entry')
                            ->label('Allows Text Entry')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                            ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('allow_file_uploads')
                            ->label('Allows File Uploads')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                            ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('allowed_file_types')
                            ->label('Allowed File Types')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->visible((bool) $activity->allowed_file_types),
                        TextEntry::make('max_file_size')
                            ->label('Max File Size (per file)')
                            ->formatStateUsing(fn (?int $state): string => $state ? ($state / 1024).' MB' : 'Not Set')
                            ->visible((bool) $activity->max_file_size),
                    ])->visible(fn() => $activity->submission_type !== 'manual'), // Hide if manual grading

                // Attached Resources for students to download
                InfolistSection::make('Attached Resources')
                    ->schema([
                        RepeatableEntry::make('resources') // Assumes 'resources' relationship on Activity model
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('File Name')
                                    ->icon('heroicon-o-document-text')
                                    ->url(fn (\App\Models\ActivityResource $record): ?string =>
                                        $record->file_path ? Storage::disk('public')->url($record->file_path) : null,
                                        shouldOpenInNewTab: true
                                    )
                                    ->visible(fn (\App\Models\ActivityResource $record): bool => (bool)$record->file_path), // Only show if there's a path
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->placeholder('No description')
                                    ->html(),
                                TextEntry::make('file_size')
                                     ->label('Size')
                                     ->formatStateUsing(fn(?int $state): string => $state ? round($state / 1024, 2) . ' KB' : ''),
                                InfolistAction::make('download_resource')
                                    ->label('Download')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(fn (\App\Models\ActivityResource $record): ?string =>
                                        $record->file_path ? Storage::disk('public')->url($record->file_path) : null
                                    )
                                    ->openUrlInNewTab()
                                    ->visible(fn (\App\Models\ActivityResource $record): bool => (bool)$record->file_path)
                                    ->color('gray'),
                            ])
                            ->columns(4)
                            ->grid(2)
                            ->hidden(fn ($record) => $record->resources->isEmpty()) // Hide section if no resources
                            ->contained(false), // Ensures it uses full width if needed
                        TextEntry::make('no_resources_placeholder')
                            ->label('')
                            ->default('No resources attached to this activity.')
                            ->visible(fn ($record) => $record->resources->isEmpty()), // Show placeholder if no resources
                    ])
                    ->collapsible(),
            ]);
    }
}