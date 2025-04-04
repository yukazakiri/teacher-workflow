<?php

namespace App\Filament\Admin\Resources\TeamResource\RelationManagers;

use App\Models\User; // Import User model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Needed for table column formatting
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = "students";

    protected static ?string $recordTitleAttribute = "name";

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make("name")
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make("email")
                ->label("Student Email") // Clarify label if different from user email
                ->email()
                // ->required() // Maybe not required if linking to user?
                ->maxLength(255)
                ->helperText(
                    'The student\'s primary contact email (might differ from their linked account).'
                ),
            Forms\Components\TextInput::make("student_id")
                ->label("Student ID")
                ->maxLength(255)
                ->helperText(
                    "Optional: School-provided student identification number."
                ),
            Forms\Components\Select::make("user_id")
                ->label("Linked User Account")
                ->relationship(
                    // Use relationship for easy loading
                    name: "user", // Name of the relationship method in Student model
                    titleAttribute: "name" // Attribute to display from User model
                    // Optional: Add condition to filter users (e.g., only show users with a 'student' role)
                    // modifyQueryUsing: fn (Builder $query) => $query->where('role', 'student')
                )
                ->searchable() // Allow searching for users by name/email
                ->preload() // Load some initial users (adjust if you have many users)
                ->placeholder("No Linked Account") // Show this when null
                // ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})") // Customize displayed option
                ->helperText(
                    "Link this student record to an existing system user account."
                )
                // ->createOptionForm([ // Optionally allow creating a User directly (use with caution)
                //     Forms\Components\TextInput::make('name')->required(),
                //     Forms\Components\TextInput::make('email')->email()->required()->unique(User::class, 'email'),
                //     Forms\Components\TextInput::make('password')->password()->required(),
                // ])
                ->nullable(), // Allow the field to be empty
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("email")
                    ->label("Student Email")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Hide by default if less important
                Tables\Columns\TextColumn::make("student_id")
                    ->label("Student ID")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("user.name") // Access linked user's name
                    ->label("Linked User")
                    ->searchable()
                    ->sortable()
                    ->placeholder("Not Linked") // Display this if user_id is null
                    // Add a link to the User resource if you have one
                    ->url(
                        fn(Model $record): ?string => $record->user_id
                            ? route(
                                "filament.admin.resources.users.edit",
                                $record->user_id
                            )
                            : null,
                        shouldOpenInNewTab: true
                    )
                    ->icon(
                        fn(Model $record): ?string => $record->user_id
                            ? "heroicon-m-user-circle"
                            : null
                    ) // Add visual cue
                    ->tooltip(
                        fn(Model $record): ?string => $record->user?->email
                    ), // Show email on hover

                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("updated_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make("user_id")
                    ->label("Account Link Status")
                    ->nullable()
                    ->trueLabel("Linked to User Account")
                    ->falseLabel("Not Linked to User Account")
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull(
                            "user_id"
                        ),
                        false: fn(Builder $query) => $query->whereNull(
                            "user_id"
                        ),
                        blank: fn(Builder $query) => $query // Show all when blank
                    ),
                // Add other relevant filters
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                // Optional: Action to bulk import students
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Optional: Quick Unlink Action
                Tables\Actions\Action::make("unlinkUser")
                    ->label("Unlink Account")
                    ->icon("heroicon-m-no-symbol")
                    ->color("warning")
                    ->requiresConfirmation()
                    ->modalHeading("Unlink User Account")
                    ->modalDescription(
                        "Are you sure you want to remove the link to the user account? The student record will remain."
                    )
                    ->action(
                        fn(Model $record) => $record->update([
                            "user_id" => null,
                        ])
                    )
                    ->visible(
                        fn(Model $record): bool => !is_null($record->user_id)
                    ), // Only show if currently linked
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Optional: Bulk Link/Unlink might be complex, usually done individually
                ]),
            ]);
    }
}
