<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkAction;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter group name'),
                    
                Textarea::make('description')
                    ->placeholder('Enter group description'),
                    
                Select::make('members')
                    ->label('Group Members')
                    ->multiple()
                    ->relationship('members', 'name')
                    ->options(
                        User::whereHas('teams', function (Builder $query) {
                            $query->where('team_id', Auth::user()->currentTeam->id);
                        })->where('id', '!=', Auth::id())->pluck('name', 'id')
                    )
                    ->preload()
                    ->searchable(),
                    
                Repeater::make('roles')
                    ->relationship()
                    ->schema([
                        Select::make('user_id')
                            ->label('Student')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Select::make('activity_role_id')
                            ->label('Role')
                            ->relationship('activityRole', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Textarea::make('notes')
                            ->placeholder('Additional notes about this role assignment'),
                    ])
                    ->columns(2)
                    ->itemLabel(fn (array $state): ?string => 
                        User::find($state['user_id'] ?? null)?->name ?? 'New Role Assignment'
                    ),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->sortable(),
                    
                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->mode === 'group'),
                    
                Action::make('auto_assign')
                    ->label('Auto-Assign Groups')
                    ->icon('heroicon-o-user-group')
                    ->action(function () {
                        // Get all students in the team
                        $students = Auth::user()->currentTeam->allUsers()
                            ->where('id', '!=', Auth::id())
                            ->get();
                            
                        // Get the activity
                        $activity = $this->getOwnerRecord();
                        
                        // Calculate number of groups
                        $groupCount = $activity->group_count ?? 4;
                        $studentsPerGroup = ceil($students->count() / $groupCount);
                        
                        // Create groups
                        $studentChunks = $students->chunk($studentsPerGroup);
                        
                        foreach ($studentChunks as $index => $chunk) {
                            $group = $activity->groups()->create([
                                'name' => 'Group ' . ($index + 1),
                                'description' => 'Auto-generated group for ' . $activity->title,
                            ]);
                            
                            // Add members to the group
                            foreach ($chunk as $student) {
                                $group->members()->attach($student->id);
                            }
                        }
                    })
                    ->visible(fn () => $this->getOwnerRecord()->mode === 'group'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Group $record): string => 
                        route('filament.admin.resources.activities.edit', [
                            'record' => $this->getOwnerRecord(),
                            'activeRelationManager' => 2, // Index of the SubmissionsRelationManager
                            'group_id' => $record->id,
                        ])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
