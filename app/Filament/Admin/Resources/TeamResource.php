<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeamResource\Pages;
use App\Filament\Admin\Resources\TeamResource\RelationManagers;
use App\Models\Team; // Import the Team model
use App\Models\User; // Import the User model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get; // Needed for conditional logic
use Filament\Forms\Set; // Needed for potential reactive updates (optional here)
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

// Needed for options callback
// For custom validation

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Changed icon to reflect 'teams'

    protected static ?string $navigationGroup = 'Team Management'; // Group related resources

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Core Team Information')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    Forms\Components\Select::make('user_id')
                        ->label('Owner')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        // Usually, you don't change the owner easily in an admin panel
                        // ->disabled(fn (string $operation) => $operation !== 'create')
                        ->helperText('The primary owner of this team/class.')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('join_code')
                        ->label('Join Code')
                        ->disabled() // Generated automatically
                        ->helperText(
                            'Auto-generated code for students/members to join.'
                        )
                        ->columnSpan(1),
                    Forms\Components\Placeholder::make('created_at')
                        ->label('Created')
                        ->content(
                            fn (
                                ?Team $record
                            ): string => $record?->created_at?->diffForHumans() ??
                                '-'
                        )
                        ->columnSpan(1)
                        ->visibleOn('edit'),
                    Forms\Components\Placeholder::make('updated_at')
                        ->label('Last Updated')
                        ->content(
                            fn (
                                ?Team $record
                            ): string => $record?->updated_at?->diffForHumans() ??
                                '-'
                        )
                        ->columnSpan(1)
                        ->visibleOn('edit'),
                    // Personal Team is usually managed by Jetstream, maybe hide or disable
                    Forms\Components\Toggle::make('personal_team')
                        ->disabled()
                        ->helperText(
                            'Indicates if this is a user\'s personal team.'
                        )
                        ->columnSpanFull()
                        ->visible(false), // Often hidden in admin panels
                    Forms\Components\Hidden::make('onboarding_step'), // Likely managed internally
                ]),

            Forms\Components\Section::make('Grading System Configuration')
                ->description(
                    'Select the grading system type and configure its settings.'
                )
                ->schema([
                    Forms\Components\Select::make('grading_system_type')
                        ->label('Grading System Type')
                        ->options([
                            Team::GRADING_SYSTEM_SHS => 'K-12 Senior High School (Component Weighted)',
                            Team::GRADING_SYSTEM_COLLEGE => 'College / University',
                            // Add null option if allowing unconfigured
                            // null => 'Not Configured',
                        ])
                        ->required()
                        ->live() // Important for conditional fields
                        ->afterStateUpdated(function (Set $set) {
                            // Reset dependent fields when type changes
                            $set('college_grading_scale', null);
                            $set('shs_ww_weight', null);
                            $set('shs_pt_weight', null);
                            $set('shs_qa_weight', null);
                            $set('college_prelim_weight', null);
                            $set('college_midterm_weight', null);
                            $set('college_final_weight', null);
                        }),

                    // --- SHS Specific Fields ---
                    Forms\Components\Fieldset::make('SHS Component Weights')
                        ->label('SHS Component Weights (%)')
                        ->columns(3)
                        ->visible(
                            fn (Get $get): bool => $get(
                                'grading_system_type'
                            ) === Team::GRADING_SYSTEM_SHS
                        )
                        ->schema([
                            Forms\Components\TextInput::make('shs_ww_weight')
                                ->label('Written Works (WW)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->required(
                                    fn (Get $get): bool => $get(
                                        'grading_system_type'
                                    ) === Team::GRADING_SYSTEM_SHS
                                ),
                            Forms\Components\TextInput::make('shs_pt_weight')
                                ->label('Performance Tasks (PT)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->required(
                                    fn (Get $get): bool => $get(
                                        'grading_system_type'
                                    ) === Team::GRADING_SYSTEM_SHS
                                ),
                            Forms\Components\TextInput::make('shs_qa_weight')
                                ->label('Quarterly Assessment (QA)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->required(
                                    fn (Get $get): bool => $get(
                                        'grading_system_type'
                                    ) === Team::GRADING_SYSTEM_SHS
                                ),
                            Forms\Components\Placeholder::make(
                                'shs_total_weight'
                            )
                                ->label('Total SHS Weight')
                                ->content(function (Get $get): string {
                                    $total =
                                        (int) $get('shs_ww_weight') +
                                        (int) $get('shs_pt_weight') +
                                        (int) $get('shs_qa_weight');
                                    $color =
                                        $total === 100
                                            ? 'text-success-600'
                                            : 'text-danger-600';

                                    return "<span class='font-bold {$color}'>{$total}%</span> (Must equal 100%)";
                                })
                                ->extraAttributes([
                                    'class' => 'filament-forms-placeholder-component-html',
                                ]) // This helps Tailwind process the HTML classes
                                // ->html() // <-- REMOVE THIS LINE, it doesn't exist in Filament 3
                                ->columnSpanFull(),
                        ]),
                    // Add custom validation rule for SHS weights
                    // ->validationAttribute("SHS Weights") // Custom attribute name for error message
                    // ->rules([
                    //     function (Get $get) {
                    //         return function (
                    //             string $attribute,
                    //             $value,
                    //             \Closure $fail
                    //         ) use ($get) {
                    //             if (
                    //                 $get("grading_system_type") ===
                    //                 Team::GRADING_SYSTEM_SHS
                    //             ) {
                    //                 $total =
                    //                     (int) $get("shs_ww_weight") +
                    //                     (int) $get("shs_pt_weight") +
                    //                     (int) $get("shs_qa_weight");
                    //                 if ($total !== 100) {
                    //                     $fail(
                    //                         "The total SHS Component Weights (WW + PT + QA) must sum up to exactly 100%. Current total: " .
                    //                             $total .
                    //                             "%"
                    //                     );
                    //                 }
                    //             }
                    //         };
                    //     },
                    // ]),

                    // --- College Specific Fields ---
                    Forms\Components\Fieldset::make(
                        'College Grading Configuration'
                    )
                        ->visible(
                            fn (Get $get): bool => $get(
                                'grading_system_type'
                            ) === Team::GRADING_SYSTEM_COLLEGE
                        )
                        ->schema([
                            Forms\Components\Select::make(
                                'college_grading_scale'
                            )
                                ->label('College Grading Scale / Type')
                                ->options([
                                    // Group options for clarity
                                    'GWA Based Scales' => [
                                        Team::COLLEGE_SCALE_GWA_5_POINT => 'GWA Based - 5 Point Scale (1.00 highest)',
                                        Team::COLLEGE_SCALE_GWA_4_POINT => 'GWA Based - 4 Point Scale (4.0 highest)',
                                        Team::COLLEGE_SCALE_GWA_PERCENTAGE => 'GWA Based - Percentage Scale (100% highest)',
                                    ],
                                    'Term Based Scales' => [
                                        Team::COLLEGE_SCALE_TERM_5_POINT => 'Term Based (Prelim, Midterm, Final) - 5 Point Scale',
                                        Team::COLLEGE_SCALE_TERM_4_POINT => 'Term Based (Prelim, Midterm, Final) - 4 Point Scale',
                                        Team::COLLEGE_SCALE_TERM_PERCENTAGE => 'Term Based (Prelim, Midterm, Final) - Percentage Scale',
                                    ],
                                ])
                                ->required(
                                    fn (Get $get): bool => $get(
                                        'grading_system_type'
                                    ) === Team::GRADING_SYSTEM_COLLEGE
                                )
                                ->live() // Make live to show/hide term weights
                                ->searchable(),

                            // College Term Weights (only if a Term scale is selected)
                            Forms\Components\Fieldset::make(
                                'College Term Weights'
                            )
                                ->label('College Term Weights (%)')
                                ->columns(3)
                                ->visible(function (Get $get): bool {
                                    $scale = $get('college_grading_scale');

                                    // Check if the selected scale is one of the TERM scales
                                    return $scale &&
                                        in_array(
                                            $scale,
                                            Team::COLLEGE_TERM_SCALES
                                        );
                                })
                                ->schema([
                                    Forms\Components\TextInput::make(
                                        'college_prelim_weight'
                                    )
                                        ->label('Prelim Weight')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->suffix('%')
                                        ->required(function (Get $get): bool {
                                            $scale = $get(
                                                'college_grading_scale'
                                            );

                                            return $scale &&
                                                in_array(
                                                    $scale,
                                                    Team::COLLEGE_TERM_SCALES
                                                );
                                        }),
                                    Forms\Components\TextInput::make(
                                        'college_midterm_weight'
                                    )
                                        ->label('Midterm Weight')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->suffix('%')
                                        ->required(function (Get $get): bool {
                                            $scale = $get(
                                                'college_grading_scale'
                                            );

                                            return $scale &&
                                                in_array(
                                                    $scale,
                                                    Team::COLLEGE_TERM_SCALES
                                                );
                                        }),
                                    Forms\Components\TextInput::make(
                                        'college_final_weight'
                                    )
                                        ->label('Final Weight')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->suffix('%')
                                        ->required(function (Get $get): bool {
                                            $scale = $get(
                                                'college_grading_scale'
                                            );

                                            return $scale &&
                                                in_array(
                                                    $scale,
                                                    Team::COLLEGE_TERM_SCALES
                                                );
                                        }),
                                    Forms\Components\Placeholder::make(
                                        'college_term_total_weight'
                                    )
                                        ->label('Total Term Weight')
                                        ->content(function (Get $get): string {
                                            $total =
                                                (int) $get(
                                                    'college_prelim_weight'
                                                ) +
                                                (int) $get(
                                                    'college_midterm_weight'
                                                ) +
                                                (int) $get(
                                                    'college_final_weight'
                                                );
                                            $color =
                                                $total === 100
                                                    ? 'text-success-600'
                                                    : 'text-danger-600';

                                            return "<span class='font-bold {$color}'>{$total}%</span> (Must equal 100%)";
                                        })
                                        ->extraAttributes([
                                            'class' => 'filament-forms-placeholder-component-html',
                                        ]) // This helps Tailwind process the HTML classes
                                        // ->html() // <-- REMOVE THIS LINE, it doesn't exist in Filament 3
                                        ->columnSpanFull(),
                                ]),
                            // Add custom validation rule for College term weights
                            // ->validationAttribute("College Term Weights") // Custom attribute name for error message
                            // ->rules([
                            //     function (Get $get) {
                            //         return function (
                            //             string $attribute,
                            //             $value,
                            //             \Closure $fail
                            //         ) use ($get) {
                            //             $scale = $get(
                            //                 "college_grading_scale"
                            //             );
                            //             if (
                            //                 $scale &&
                            //                 in_array(
                            //                     $scale,
                            //                     Team::COLLEGE_TERM_SCALES
                            //                 )
                            //             ) {
                            //                 $total =
                            //                     (int) $get(
                            //                         "college_prelim_weight"
                            //                     ) +
                            //                     (int) $get(
                            //                         "college_midterm_weight"
                            //                     ) +
                            //                     (int) $get(
                            //                         "college_final_weight"
                            //                     );
                            //                 if ($total !== 100) {
                            //                     $fail(
                            //                         "The total College Term Weights (Prelim + Midterm + Final) must sum up to exactly 100%. Current total: " .
                            //                             $total .
                            //                             "%"
                            //                     );
                            //                 }
                            //             }
                            //         };
                            //     },
                            // ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn (Team $record): string => 'ID: '.$record->id
                    ), // Show UUID easily
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gradingSystemDescription') // Use the accessor from the model!
                    ->label('Grading System')
                    ->wrap() // Allow wrapping for longer descriptions
                    ->tooltip(function (Team $record): string {
                        // Provide more detail on hover if needed
                        $details = [];
                        if ($record->usesShsGrading()) {
                            $details[] = "SHS Weights: WW({$record->shs_ww_weight}%) PT({$record->shs_pt_weight}%) QA({$record->shs_qa_weight}%)";
                        } elseif ($record->usesCollegeTermGrading()) {
                            $details[] = "Term Weights: Prelim({$record->college_prelim_weight}%) Midterm({$record->college_midterm_weight}%) Final({$record->college_final_weight}%)";
                        }

                        return $details
                            ? implode(' | ', $details)
                            : 'Details unavailable';
                    }),
                Tables\Columns\TextColumn::make('join_code')
                    ->label('Join Code')
                    ->copyable()
                    ->copyMessage('Join code copied!')
                    ->icon('heroicon-o-clipboard-document')
                    ->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users') // Assumes 'users' relationship exists
                    ->label('Members')
                    ->sortable(),
                Tables\Columns\TextColumn::make('students_count')
                    ->counts('students') // Assumes 'students' relationship exists
                    ->label('Students')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grading_system_type')
                    ->label('Grading System')
                    ->options([
                        Team::GRADING_SYSTEM_SHS => 'SHS',
                        Team::GRADING_SYSTEM_COLLEGE => 'College',
                    ]),
                Tables\Filters\SelectFilter::make('owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Owner'),
                // You could add more complex filters based on college_grading_scale if needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manageMembers')
                    ->label('Members')
                    ->icon('heroicon-o-user-group') // More specific icon
                    ->color('info') // Distinguish from edit/view
                    ->tooltip('Manage Team Members & Invitations')
                    ->url(
                        fn (Team $record): string => static::getUrl('members', [
                            'record' => $record,
                        ])
                    ),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Keep relation managers for detailed views
            RelationManagers\UsersRelationManager::class,
            RelationManagers\TeamInvitationsRelationManager::class,
            RelationManagers\StudentsRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
            RelationManagers\ExamsRelationManager::class,
            // Add other relevant relation managers if needed
            // RelationManagers\SchedulesRelationManager::class,
            // RelationManagers\ClassResourcesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
            // "view" => Pages\ViewTeam::route("/{record}"), // Good practice to add a view page
            'members' => Pages\ManageTeamMembers::route('/{record}/members'), // Your custom page route
        ];
    }

    // Optional: Add a simple View page if you don't have one
    // Create a file: app/Filament/Admin/Resources/TeamResource/Pages/ViewTeam.php
    /*
     <?php
     namespace App\Filament\Admin\Resources\TeamResource\Pages;
     use App\Filament\Admin\Resources\TeamResource;
     use Filament\Actions;
     use Filament\Resources\Pages\ViewRecord;

     class ViewTeam extends ViewRecord {
         protected static string $resource = TeamResource::class;

         protected function getHeaderActions(): array {
             return [
                 Actions\EditAction::make(),
                 Actions\Action::make('manageMembers')
                     ->label('Manage Members')
                     ->icon('heroicon-o-user-group')
                     ->color('info')
                     ->url(fn () => TeamResource::getUrl('members', ['record' => $this->record])),
                 Actions\DeleteAction::make(),
             ];
         }
     }
     */

    // Improve global search result presentation (Optional)
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'owner.name', 'join_code'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Owner' => $record->owner->name,
            'Grading' => $record->gradingSystemDescription, // Use the accessor
        ];
    }
}
