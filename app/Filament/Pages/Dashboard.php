<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Widgets\PendingInvitationsTableWidget;
use App\Filament\Widgets\TeamMembersTableWidget;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\PrismChatService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Livewire;
use Filament\Pages\Dashboard as PagesDashboard;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Prism\Prism\Facades\PrismServer;

class Dashboard extends PagesDashboard
{
    protected static string $routePath = "/";

    protected static ?int $navigationSort = -2;

    public const ONBOARDING_STUDENT_THRESHOLD = 5;

    // protected static ?string $navigationLabel = 'Home';
    //
    public static function getNavigationLabel(): string
    {
        return __("Home");
    }
    public function mount()
    {
        FilamentView::registerRenderHook(
            name: PanelsRenderHook::HEAD_START,
            hook: fn() => Blade::render('@mingles'),
        );
    }

    /**
     * @var view-string
     */
    protected static string $view = "filament.pages.dashboard";

    // Add support for quick actions

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return static::$navigationIcon ??
            (FilamentIcon::resolve("panels::pages.dashboard.navigation-item") ??
                (Filament::hasTopNavigation()
                    ? "heroicon-m-chat-bubble-oval-left-ellipsis"
                    : "heroicon-o-chat-bubble-oval-left-ellipsis"));
    }

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            TeamMembersTableWidget::class,
            PendingInvitationsTableWidget::class,
            ...Filament::getWidgets(),
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    /**
     * @return int | string | array<string, int | string | null>
     */
    public function getColumns(): int|string|array
    {
        return 1; // Set to 1 to match the Claude interface's centered layout
    }

    public function getTitle(): string|Htmlable
    {
        // Personalized title
        return "Welcome, " . Auth::user()->name;
    }

    /**
     * Get the available chat styles.
     */
    public function getAvailableStyles(): array
    {
        $chatService = app(PrismChatService::class);

        return $chatService->getAvailableStyles();
    }

    /**
     * Determine the current onboarding state for the user's team.
     * 0 = No onboarding needed / completed
     * 1 = Initial: Needs to add students (new team, 0 students)
     * 2 = Next Step: Needs to create activities (reached student threshold)
     */
    public function getOnboardingState(): int
    {
        $user = Auth::user();
        // Eager load students count and ensure team exists
        $team = $user?->currentTeam()->withCount("students")->first();

        if (!$team) {
            \Illuminate\Support\Facades\Log::debug(
                "getOnboardingState: No current team found.",
                ["user_id" => $user->id]
            );

            return 0; // No team context
        }

        // Ensure onboarding_step is treated as int
        $currentStep = (int) $team->onboarding_step;
        $studentCount = $team->students_count; // Use eager loaded count

        \Illuminate\Support\Facades\Log::debug(
            "getOnboardingState: Checking state",
            [
                "team_id" => $team->id,
                "user_id" => $user->id,
                "current_step" => $currentStep,
                "student_count" => $studentCount,
                "threshold" => self::ONBOARDING_STUDENT_THRESHOLD,
            ]
        );

        // Check for initial onboarding (State 1)
        // Only show if they haven't dismissed step 1 yet (step is 0)
        // Optional: Add check for team creation time if desired
        // $teamCreatedRecently = $team->created_at->diffInMinutes(now()) < (60 * 24 * 7); // e.g., within 7 days

        // State 1: Team exists, 0 students, haven't completed step 0
        // Note: We might not need teamCreatedRecently if we rely purely on step=0 and count=0
        if ($currentStep === 0 && $studentCount === 0) {
            \Illuminate\Support\Facades\Log::debug(
                "getOnboardingState: Returning State 1"
            );

            return 1;
        }

        // Check for second step onboarding (State 2)
        // Show if they haven't dismissed step 2 yet (step <= 1) AND have reached the student threshold
        if (
            $currentStep <= 1 &&
            $studentCount >= self::ONBOARDING_STUDENT_THRESHOLD
        ) {
            \Illuminate\Support\Facades\Log::debug(
                "getOnboardingState: Returning State 2"
            );

            return 2;
        }

        // Default: No onboarding needed for now
        \Illuminate\Support\Facades\Log::debug(
            "getOnboardingState: Returning State 0"
        );

        return 0;
    }

    /**
     * Livewire action to mark an onboarding step as seen/completed.
     */
    public function markOnboardingStepComplete(int $stepJustCompleted): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam()->first();

        if ($team) {
            // Ensure we only move forward, don't accidentally revert
            if ($stepJustCompleted > (int) $team->onboarding_step) {
                \Illuminate\Support\Facades\Log::info(
                    "Marking onboarding step complete",
                    [
                        "team_id" => $team->id,
                        "user_id" => $user->id,
                        "step" => $stepJustCompleted,
                    ]
                );
                $team->update(["onboarding_step" => $stepJustCompleted]);
                // No need to force refresh usually, Alpine handles UI closure.
                // $this->dispatch('refresh-dashboard'); // Example if you needed related components to update
            } else {
                \Illuminate\Support\Facades\Log::info(
                    "Skipping onboarding step update",
                    [
                        "team_id" => $team->id,
                        "user_id" => $user->id,
                        "step" => $stepJustCompleted,
                        "current_step" => $team->onboarding_step,
                    ]
                );
            }
        } else {
            \Illuminate\Support\Facades\Log::warning(
                "Could not mark onboarding step: No current team found.",
                ["user_id" => $user->id]
            );
        }
        // We don't necessarily need to close the modal here via Livewire
        // as the Alpine @click='open = false' will handle the UI closure.
        // The next time the page loads, getOnboardingState() will return the updated state.
    }

    /**
     * Get the quick actions for the chat.
     */
    public function getQuickActions(): array
    {
        return [
            [
                "name" => "Polish prose",
                "description" => "Improve writing style and clarity",
                "prompt" =>
                    "Please polish the following text to improve its clarity, style, and professionalism: ",
            ],
            [
                "name" => "Generate questions",
                "description" => "Create discussion questions for students",
                "prompt" =>
                    "Generate 5 thought-provoking discussion questions for students about the following topic: ",
            ],
            [
                "name" => "Write a memo",
                "description" => "Create a professional memo",
                "prompt" =>
                    "Write a professional memo about the following topic: ",
            ],
            [
                "name" => "Summarize",
                "description" => "Create a concise summary",
                "prompt" =>
                    "Please summarize the following text in a clear and concise manner: ",
            ],
        ];
    }

    /**
     * Get recent chats for the current user.
     */
    public function getRecentChats(): array
    {
        $recentChats = \App\Models\Conversation::where("user_id", Auth::id())
            ->orderBy("last_activity_at", "desc")
            ->limit(5)
            ->get()
            ->map(function ($chat) {
                return [
                    "id" => $chat->id,
                    "title" => $chat->truncated_title,
                    "model" => $chat->model,
                    "last_activity" => $chat->last_activity_at->diffForHumans(),
                    "avatar_letter" => strtoupper(substr($chat->title, 0, 1)),
                    "color" => $this->getRandomColor(),
                ];
            })
            ->toArray();

        return $recentChats;
    }

    /**
     * Get a random color for the chat avatar.
     */
    private function getRandomColor(): string
    {
        $colors = ["primary", "success", "warning", "danger", "info"];

        return $colors[array_rand($colors)];
    }

    /**
     * Get data for the view.
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $currentTeam = $user?->currentTeam()->first(); // Fetch the team instance
        $onboardingState = $this->getOnboardingState();

        return [
            "heading" => $this->getHeading(),
            "subheading" => $this->getSubheading(),
            "availableModels" => PrismServer::prisms()->pluck("name"),
            "availableStyles" => $this->getAvailableStyles(),
            "quickActions" => $this->getQuickActions(),
            "recentChats" => $this->getRecentChats(),
            "conversationId" => request()->get("conversation_id"),
            // 'needsOnboarding' => $this->needsOnboarding(), // Remove old flag
            "onboardingState" => $onboardingState, // Pass the state
            "studentResourceCreateUrl" => $currentTeam // Changed variable name for clarity
                ? StudentResource::getUrl("index", ["tenant" => $currentTeam]) // Use Resource URL generation
                : "#",
            // Add URL for creating activities - Make sure ActivityResource exists and has a 'create' page
            "activityResourceCreateUrl" => $currentTeam
                ? ActivityResource::getUrl("create", ["tenant" => $currentTeam])
                : "#",
            "studentThreshold" => self::ONBOARDING_STUDENT_THRESHOLD, // Pass threshold for display if needed
            "needsRoleSelection" => $this->needsRoleSelection(),
        ];
    }

    public function needsRoleSelection(): bool
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$team) return false;

        $membership = DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();

        return $membership && (is_null($membership->role) || $membership->role === 'pending');
    }
}
