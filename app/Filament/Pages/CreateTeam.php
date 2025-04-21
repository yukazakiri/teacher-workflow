<?php

namespace App\Filament\Pages;

use App\Forms\Components\CreateTeamLayout;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class CreateTeam extends RegisterTenant
{
    public $activeOption = "create";

    // protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    // protected static string $view = 'filament.pages.create-team';

    public function mount(): void
    {
        // parent::mount();

        // Initialize the active option from query parameter if present
        $this->activeOption = Request::query("option", "create");
    }

    public static function getLabel(): string
    {
        return "Get Started with Class";
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            CreateTeamLayout::make()->schema([
                Section::make()->schema([
                    Grid::make()
                        ->schema([
                            Radio::make("option")
                                ->label("Choose an option")
                                ->options([
                                    "create" => "Create New Class",
                                    "join" => "Join Existing Class",
                                ])
                                ->default($this->activeOption)
                                ->live()
                                ->inline()
                                ->afterStateUpdated(function (string $state) {
                                    $this->activeOption = $state;
                                })
                                ->columnSpanFull(),
                        ])
                        ->columns([
                            "default" => 1,
                            "md" => 2,
                        ]),

                    // Create Class Card
                    Card::make()
                        ->schema([
                            Grid::make()
                                ->schema([
                                    Placeholder::make("create_icon")
                                        ->content(
                                            new HtmlString('
                                                        <div class="flex justify-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                            </svg>
                                                        </div>
                                                    ')
                                        )
                                        ->columnSpanFull(),

                                    Placeholder::make("create_instructions")
                                        ->content(
                                            new HtmlString('
                                                        <div class="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create a new class</h3>
                                                            <p>Create a new class that you will manage as a teacher.</p>
                                                            <p>After creating your class, you can:</p>
                                                            <ul class="list-disc pl-5 space-y-1">
                                                                <li>Add students via email invitations</li>
                                                                <li>Share a join code with students for self-enrollment</li>
                                                                <li>Create activities, exams and assignments</li>
                                                                <li>Upload teaching resources</li>
                                                            </ul>
                                                        </div>
                                                    ')
                                        )
                                        ->columnSpanFull(),

                                    TextInput::make("name")
                                        ->label("Class Name")
                                        ->placeholder(
                                            "e.g., Biology 101, Math 202"
                                        )
                                        ->maxLength(255)
                                        ->helperText(
                                            "Choose a descriptive name for your class"
                                        )
                                        ->translateLabel()
                                        ->required(
                                            fn() => $this->activeOption ===
                                                "create"
                                        )
                                        ->visible(
                                            fn() => $this->activeOption ===
                                                "create"
                                        )
                                        ->disabled(
                                            fn() => $this->activeOption !==
                                                "create"
                                        )
                                        ->columnSpanFull(),
                                ])
                                ->columns([
                                    "default" => 1,
                                    "sm" => 1,
                                ]),
                        ])
                        ->visible(fn() => $this->activeOption === "create")
                        ->columnSpanFull(),

                    // Join Class Card
                    Card::make()
                        ->schema([
                            Grid::make()
                                ->schema([
                                    Placeholder::make("join_icon")
                                        ->content(
                                            new HtmlString('
                                                        <div class="flex justify-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                            </svg>
                                                        </div>
                                                    ')
                                        )
                                        ->columnSpanFull(),

                                    Placeholder::make("join_instructions")
                                        ->content(
                                            new HtmlString('
                                                        <div class="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Join an existing class</h3>
                                                            <p>Enter the 6-character class code provided by your teacher to join an existing class.</p>
                                                            <p>After joining, you\'ll have access to:</p>
                                                            <ul class="list-disc pl-5 space-y-1">
                                                                <li>Class activities and assignments</li>
                                                                <li>Learning resources</li>
                                                                <li>Class discussions</li>
                                                                <li>Your grades and feedback</li>
                                                            </ul>
                                                        </div>
                                                    ')
                                        )
                                        ->columnSpanFull(),

                                    TextInput::make("join_code")
                                        ->label("Class Code")
                                        ->placeholder(
                                            "Enter the 6-digit class code"
                                        )
                                        ->maxLength(6)
                                        ->minLength(6)
                                        ->helperText(
                                            "The code should be 6 characters (letters and numbers)"
                                        )
                                        ->translateLabel()
                                        ->required(
                                            fn() => $this->activeOption ===
                                                "join"
                                        )
                                        ->visible(
                                            fn() => $this->activeOption ===
                                                "join"
                                        )
                                        ->disabled(
                                            fn() => $this->activeOption !==
                                                "join"
                                        )
                                        ->columnSpanFull(),
                                ])
                                ->columns([
                                    "default" => 1,
                                    "sm" => 1,
                                ]),
                        ])
                        ->visible(fn() => $this->activeOption === "join")
                        ->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make("submit")
                ->label(
                    fn() => $this->activeOption === "create"
                        ? "Create Class"
                        : "Join Class"
                )
                ->icon(
                    fn() => $this->activeOption === "create"
                        ? "heroicon-o-plus-circle"
                        : "heroicon-o-user-plus"
                )
                ->color("primary")
                ->size("lg")
                ->extraAttributes([
                    "class" => "w-full justify-center",
                ])
                ->action(function () {
                    if (
                        isset($this->data["option"]) &&
                        $this->data["option"] == "join"
                    ) {
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
    protected function joinExistingTeam(
        array $data
    ) {
        if (empty($data["join_code"])) {
            throw ValidationException::withMessages([
                "join_code" => "Please enter a valid class code.",
            ]);
        }

        $joinCode = trim($data["join_code"]);

        // Find team with matching join code
        $team = Team::where("join_code", $joinCode)->first();
        if (!$team) {
            Notification::make()
                ->danger()
                ->title("Invalid Class Code")
                ->body(
                    "The provided class code is invalid. Please check and try again."
                )
                ->send();

            throw ValidationException::withMessages([
                "join_code" =>
                    "The provided class code is invalid. Please check and try again.",
            ]);
        }

        $user = Auth::user(); // Get the authenticated user

        // Check if user is already a member of this team
        if ($user->belongsToTeam($team)) {
            // Optionally switch to the team if already a member but not current
            if ($user->currentTeam?->id !== $team->id) {
                $user->switchTeam($team);
                Notification::make()
                    ->info()
                    ->title("Switched Class")
                    ->body(
                        "You are already a member of {$team->name}. Switched to this class."
                    )
                    ->send();

                // Redirect to the dashboard of the joined team
                return redirect(Dashboard::getUrl(["tenant" => $team])); // <-- Use Dashboard::getUrl()
            } else {
                // Already a member and it's the current team
                throw ValidationException::withMessages([
                    "join_code" =>
                        "You are already a member of this class and it's your current class.",
                ]);
            }
        }

        // Add current user to the team using membership model
        try {
            \Illuminate\Support\Facades\Log::debug(
                "Attempting to add user to team",
                ["team_id" => $team->id, "user_id" => $user->id]
            );
            DB::table("team_user")->insert([
                "team_id" => $team->id,
                "user_id" => $user->id,
                "role" => "student", // Default role when joining via code
                "created_at" => now(),
                "updated_at" => now(),
            ]);
            \Illuminate\Support\Facades\Log::debug(
                "User successfully added to team"
            );

            // Switch the current team for the user
            $user->switchTeam($team);
            \Illuminate\Support\Facades\Log::debug("Switched user's team");

            Notification::make()
                ->success()
                ->title("Successfully Joined Class")
                ->body("You have been added to {$team->name}")
                ->send();

            // Redirect to the dashboard of the joined team
            return redirect(Dashboard::getUrl(["tenant" => $team])); // <-- Use Dashboard::getUrl()
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Error adding user to team",
                [
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]
            );

            Notification::make()
                ->danger()
                ->title("Error")
                ->body("Unable to join class. Please try again later.")
                ->send();

            throw ValidationException::withMessages([
                "join_code" => "Unable to join class. Please try again later.",
            ]);
        }
    }

    /**
     * Handle creating a new team
     */
    protected function createNewTeam(array $data)
    {
        // We should be in create mode to use this
        if ($this->activeOption !== "create") {
            // This shouldn't happen if validation/visibility works, but as a safeguard
            Notification::make()
                ->warning()
                ->title("Incorrect Mode")
                ->body("Attempted to create class while in join mode.")
                ->send();

            // Optionally redirect back or throw an error
            return redirect()->back();
        }

        // Validate name field
        if (empty($data["name"])) {
            throw ValidationException::withMessages([
                "name" => "Please enter a class name.",
            ]);
        }

        try {
            $user = Auth::user();
            \Illuminate\Support\Facades\Log::debug(
                "Creating new team with name",
                [
                    "name" => $data["name"],
                    "user_id" => $user->id,
                ]
            );

            // handleRegistration creates the team and associates the user
            $team = $this->handleRegistration([
                "name" => $data["name"],
            ]);

            // Ensure the team was created and the user is switched
            if (!$user->fresh()->belongsToTeam($team)) {
                // This indicates a problem with Jetstream's team creation/association
                \Illuminate\Support\Facades\Log::error(
                    "User was not associated with the newly created team.",
                    ["user_id" => $user->id, "team_id" => $team->id]
                );
                throw new \Exception(
                    "Failed to associate user with the new class."
                );
            }

            // Jetstream's CreateTeam action should handle switching the team automatically.
            // If not, uncomment the line below:
            // $user->switchTeam($team);

            \Illuminate\Support\Facades\Log::info(
                "New team created successfully",
                ["team_id" => $team->id, "team_name" => $team->name]
            );

            // Show success notification
            Notification::make()
                ->success()
                ->title("Class Created Successfully")
                ->body(
                    "Your class '{$data["name"]}' has been created successfully."
                )
                ->sendToDatabase($user) // Optional: Send to DB for persistence
                ->broadcast($user); // Optional: Broadcast if using real-time features

            // Redirect to the dashboard of the newly created team
            return redirect(Dashboard::getUrl(["tenant" => $team])); // <-- Use Dashboard::getUrl()
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error creating team", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title("Error Creating Class")
                ->body("Unable to create class: " . $e->getMessage()) // Provide more context if safe
                ->send();

            // Re-throw or handle specifically
            if ($e instanceof ValidationException) {
                throw $e;
            }

            throw ValidationException::withMessages([
                "name" =>
                    "Unable to create class due to an internal error. Please try again later.",
            ]);
        }
    }

    /**
     * The original form submit handler (used only for creating new teams)
     * This method is called by createNewTeam.
     */
    protected function handleRegistration(array $data): Model
    {
        // This action should create the team, add the user as owner, and switch the user's current team.
        return app(\App\Actions\Jetstream\CreateTeam::class)->create(
            Auth::user(),
            $data
        );
    }
}
