<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Models\Activity; // Use Activity model
use App\Models\Group;
use App\Models\Student;
use App\Models\User; // Use Student model for options
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    // Conditionally display this relation manager
    public static function canViewForRecord(
        Model $ownerRecord,
        string $pageClass
    ): bool {
        // Only show this manager if the Activity mode is 'group'
        return $ownerRecord instanceof Activity &&
            $ownerRecord->mode === 'group';
    }

    public function form(Form $form): Form
    {
        $activity = $this->getOwnerRecord();
        $teamId = Auth::user()->currentTeam->id;

        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->placeholder('Enter group name (e.g., Group Alpha)'),

            Textarea::make('description')
                ->rows(3)
                ->placeholder(
                    'Optional: Add a short description for the group'
                ),

            Select::make('members')
                ->label('Group Members')
                ->multiple()
                ->relationship(
                    'members',
                    'name',
                    modifyQueryUsing: fn (
                        Builder $query // Ensure we only select students from the current team
                    ) => $query
                        ->whereHas(
                            'teams',
                            fn ($q) => $q->where(
                                'team_id',
                                Auth::user()->currentTeam->id
                            )
                        )
                        ->where('id', '!=', Auth::id()) // Exclude teacher
                    // Optional: Add role check if needed (e.g., only users with 'student' role)
                    // ->whereHas('teamRoles', fn($q) => $q->where('role', 'student'))
                )
                ->options(
                    // Provide options directly for better performance if list isn't huge
                    Student::where('team_id', $teamId) // Assuming Student model exists and is relevant
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->preload()
                ->searchable()
                ->helperText('Select the students who belong to this group.'),

            // Simplified Role Assignment (if needed directly on group, otherwise handle via submissions maybe?)
            // If roles are defined on Activity, maybe assign them per student submission?
            // Repeater::make('roles')
            //     ->relationship() // Assumes Group hasMany GroupRoleAssignment
            //     ->schema([
            //         Select::make('user_id') // Should be student_id if using Student model
            //             ->label('Student')
            //             // Filter options to only members of this specific group (tricky on create)
            //             ->relationship('user', 'name') // Adjust relationship if needed
            //             ->searchable()
            //             ->preload()
            //             ->required(),
            //         Select::make('activity_role_id')
            //             ->label('Role')
            //             ->relationship('activityRole', 'name', modifyQueryUsing: fn (Builder $query) =>
            //                 $query->where('activity_id', $activity->id) // Filter roles for this activity
            //             )
            //             ->options(fn() => $activity->roles()->pluck('name', 'id')) // Use activity roles
            //             ->searchable()
            //             ->preload()
            //             ->required(),
            //         Textarea::make('notes')
            //             ->placeholder('Optional notes for this role'),
            //     ])
            //     ->columns(2)
            //     ->itemLabel(fn (array $state): ?string =>
            //         User::find($state['user_id'] ?? null)?->name ?? 'New Role Assignment'
            //     )
            //     ->addActionLabel('Assign Role'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn (Group $record): ?string => $record->description
                    ),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members') // Make sure the 'members' relationship exists on Group model
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('submissions_count') // Requires Group hasMany Submissions relation
                    ->label('Submissions')
                    ->counts('submissions') // Make sure the 'submissions' relationship exists on Group model
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add filters if needed, e.g., Has members?
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),

                Action::make('auto_assign')
                    ->label('Auto-Assign Students')
                    ->icon('heroicon-o-user-group')
                    ->color('secondary')
                    ->requiresConfirmation()
                    ->modalHeading('Auto-Assign Students to Groups')
                    ->modalDescription(
                        'This will distribute all active students in the class evenly among the specified number of groups. Existing groups and assignments might be affected or removed. Are you sure?'
                    )
                    ->form([
                        TextInput::make('group_count')
                            ->label('Desired Number of Groups')
                            ->numeric()
                            ->minValue(2)
                            ->default(
                                fn () => $this->getOwnerRecord()->group_count ??
                                    4
                            ) // Use value from activity if set
                            ->required(),
                        // Add option to clear existing groups?
                        // Forms\Components\Checkbox::make('clear_existing')
                        //    ->label('Remove existing groups before assigning')
                        //    ->default(true),
                    ])
                    ->action(function (array $data) {
                        $activity = $this->getOwnerRecord();
                        $teamId = Auth::user()->currentTeam->id;
                        $students = Student::where('team_id', $teamId)
                            ->where('status', 'active')
                            ->inRandomOrder() // Shuffle for randomness
                            ->get();

                        if ($students->isEmpty()) {
                            Notification::make()
                                ->title('No active students found to assign.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $groupCount = max(2, (int) $data['group_count']); // Ensure at least 2 groups
                        $studentsPerGroup = max(
                            1,
                            floor($students->count() / $groupCount)
                        );
                        $remainder = $students->count() % $groupCount;

                        // Optional: Clear existing groups first
                        // if ($data['clear_existing']) {
                        //     $activity->groups()->each(fn ($group) => $group->delete()); // Careful with cascading deletes
                        // }
                        $activity->groups()->delete(); // Simpler delete all

                        $studentChunks = $students->chunk($studentsPerGroup); // Initial chunking

                        // Distribute remainder students
                        $assignedStudents = collect();
                        $groupsData = [];
                        for ($i = 0; $i < $groupCount; $i++) {
                            $groupsData[$i] = collect();
                        }

                        $groupIndex = 0;
                        foreach ($students as $student) {
                            $groupsData[$groupIndex]->push($student);
                            $groupIndex = ($groupIndex + 1) % $groupCount;
                        }

                        foreach ($groupsData as $index => $studentGroup) {
                            if ($studentGroup->isEmpty()) {
                                continue;
                            }

                            $group = $activity->groups()->create([
                                'name' => 'Group '.($index + 1),
                                'description' => 'Auto-assigned for '.$activity->title,
                                'team_id' => $teamId, // Ensure team_id is set if needed
                            ]);

                            $group->members()->sync($studentGroup->pluck('id')); // Sync members
                        }

                        Notification::make()
                            ->title('Students Auto-Assigned')
                            ->body(
                                "Created {$groupCount} groups and assigned {$students->count()} students."
                            )
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                // View Submissions link might be redundant if StudentSubmissionsRelationManager is used
                // Action::make('view_submissions')
                //     ->label('View Submissions')
                //     ->icon('heroicon-o-document-text')
                //     ->url(fn (Group $record): string =>
                //         // Navigate to the activity edit page and focus the submissions manager, filtering by group
                //         ActivityResource::getUrl('edit', [
                //             'record' => $this->getOwnerRecord(),
                //             // You might need a way to pass the group filter to the StudentSubmissionsRelationManager
                //             // e.g., query parameter ?submission_group_filter=GROUP_ID
                //             // This requires customizing the StudentSubmissionsRelationManager to read this parameter.
                //         ]) . '?submission_group_filter=' . $record->id // Example query param
                //     ),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading('No groups created yet')
            ->emptyStateDescription(
                'Create groups manually or use the "Auto-Assign" action.'
            );
    }
}
