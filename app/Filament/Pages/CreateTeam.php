<?php

namespace App\Filament\Pages;

use App\Models\Team;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Validation\ValidationException;

class CreateTeam extends RegisterTenant
{
    public $activeOption = 'create';

    public function mount(): void
    {
        parent::mount();

        // Initialize the active option from query parameter if present
        $this->activeOption = Request::query('option', 'create');
    }

    public static function getLabel(): string
    {
        return __('Get Started with Class');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Radio::make('option')
                                    ->label('Choose an option')
                                    ->options([
                                        'create' => 'Create New Class',
                                        'join' => 'Join Existing Class',
                                    ])
                                    ->default($this->activeOption)
                                    ->live()
                                    ->inline()
                                    ->afterStateUpdated(function (string $state) {
                                        $this->activeOption = $state;
                                    }),

                                // Create Class Section
                                Section::make('Create New Class')
                                    ->schema([
                                        Placeholder::make('create_instructions')
                                            ->content(new HtmlString('
                                                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                                                    <p>Create a new class that you will manage as a teacher.</p>
                                                    <p>After creating your class, you can:</p>
                                                    <ul class="list-disc pl-5 space-y-1">
                                                        <li>Add students via email invitations</li>
                                                        <li>Share a join code with students for self-enrollment</li>
                                                        <li>Create activities, exams and assignments</li>
                                                        <li>Upload teaching resources</li>
                                                    </ul>
                                                </div>
                                            ')),
                                        TextInput::make('name')
                                            ->label('Class Name')
                                            ->placeholder('e.g., Biology 101, Math 202')
                                            ->maxLength(255)
                                            ->helperText('Choose a descriptive name for your class')
                                            ->translateLabel()
                                            ->required(fn() => $this->activeOption === 'create')
                                            ->visible(fn() => $this->activeOption === 'create')
                                            ->disabled(fn() => $this->activeOption !== 'create'),
                                    ])
                                    ->visible(fn() => $this->activeOption === 'create'),

                                // Join Class Section
                                Section::make('Join Existing Class')
                                    ->schema([
                                        Placeholder::make('join_instructions')
                                            ->content(new HtmlString('
                                                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                                                    <p>Enter the 6-character class code provided by your teacher to join an existing class.</p>
                                                    <p>After joining, you\'ll have access to:</p>
                                                    <ul class="list-disc pl-5 space-y-1">
                                                        <li>Class activities and assignments</li>
                                                        <li>Learning resources</li>
                                                        <li>Class discussions</li>
                                                        <li>Your grades and feedback</li>
                                                    </ul>
                                                </div>
                                            ')),
                                        TextInput::make('join_code')
                                            ->label('Class Code')
                                            ->placeholder('Enter the 6-digit class code')
                                            ->maxLength(6)
                                            ->minLength(6)
                                            ->helperText('The code should be 6 characters (letters and numbers)')
                                            ->translateLabel()
                                            ->required(fn() => $this->activeOption === 'join')
                                            ->visible(fn() => $this->activeOption === 'join')
                                            ->disabled(fn() => $this->activeOption !== 'join'),
                                    ])
                                    ->visible(fn() => $this->activeOption === 'join'),
                            ])
                            ->columnSpan(2)
                    ])
                    ->columns(2)
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(fn() => $this->activeOption === 'create'
                    ? __('Create Class')
                    : __('Join Class'))
                ->action(function () {
                    if ($this->data['option'] == 'join') {
                        // dd(true);
                        return $this->joinExistingTeam($this->data);
                    } else {

                        return $this->createNewTeam($this->data);
                    }
                }),
        ];
    }

    /**
     * Handle joining an existing team
     */
    protected function joinExistingTeam(array $data): Model
    {

        if (empty($data['join_code'])) {
            throw ValidationException::withMessages([
                'join_code' => __('Please enter a valid class code.'),
            ]);
        }

        $joinCode = trim($data['join_code']);


        // Find team with matching join code
        $team = Team::where('join_code', $joinCode)->first();
        if (!$team) {
            Notification::make()
                ->danger()
                ->title(__('Invalid Class Code'))
                ->body(__('The provided class code is invalid. Please check and try again.'))
                ->send();

            throw ValidationException::withMessages([
                'join_code' => __('The provided class code is invalid. Please check and try again.'),
            ]);
        }



        // Check if user is already a member of this team
        if (Auth::user()->belongsToTeam($team)) {
            throw ValidationException::withMessages([
                'join_code' => __('You are already a member of this class.'),
            ]);
        }

        // Add current user to the team using membership model
        try {
            \Illuminate\Support\Facades\Log::debug("Attempting to add user to team");
            DB::table('team_user')->insert([
                'team_id' => $team->id,
                'user_id' => Auth::id(),
                'role' => 'student',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\Log::debug("User successfully added to team");

            // Switch the current team for the user
            Auth::user()->switchTeam($team);

            \Illuminate\Support\Facades\Log::debug("Switched user's team");

            Notification::make()
                ->success()
                ->title(__('Successfully Joined Class'))
                ->body(__('You have been added to :team', ['team' => $team->name]))
                ->send();

            return $team;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error adding user to team", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title(__('Error'))
                ->body(__('Unable to join class. Please try again later.'))
                ->send();

            throw ValidationException::withMessages([
                'join_code' => __('Unable to join class. Please try again later.'),
            ]);
        }
    }

    /**
     * Handle creating a new team
     */
    protected function createNewTeam(array $data): Model
    {
        // We should be in create mode to use this
        if ($this->activeOption !== 'create') {
            $this->activeOption = 'create'; // Force it to create mode
        }

        // Validate name field
        if (empty($data['name'])) {
            throw ValidationException::withMessages([
                'name' => __('Please enter a class name.'),
            ]);
        }

        \Illuminate\Support\Facades\Log::debug("Creating new team with name", [
            'name' => $data['name'],
            'user_id' => Auth::id()
        ]);

        return $this->handleRegistration([
            'name' => $data['name']
        ]);
    }

    /**
     * The original form submit handler (used only for creating new teams)
     */
    protected function handleRegistration(array $data): Model
    {
        return app(\App\Actions\Jetstream\CreateTeam::class)->create(Auth::user(), $data);
    }
}
