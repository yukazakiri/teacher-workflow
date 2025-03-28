<?php

namespace App\Filament\Pages;

use App\Models\Team;
use App\Models\User;
use App\Models\Student;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Livewire\ChatInterface;
use Laravel\Jetstream\Jetstream;
use App\Services\PrismChatService;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Facades\PrismServer;
use Filament\Forms\Components\Select;
use Laravel\Jetstream\TeamInvitation;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Pages\Dashboard as PagesDashboard;
use App\Filament\Widgets\TeamMembersTableWidget;
use App\Filament\Widgets\PendingInvitationsTableWidget;
use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
class Dashboard extends PagesDashboard
{
    protected static string $routePath = "/";

    protected static ?int $navigationSort = -2;
    const ONBOARDING_STUDENT_THRESHOLD = 5;
    protected static ?string $navigationLabel = "Home";
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
        return "Welcome, " . auth()->user()->name;
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
        $team = $user->currentTeam;

        if (!$team) {
            return 0; // No team context
        }

        // Ensure onboarding_step is treated as int
        $currentStep = (int) $team->onboarding_step;

        // Check for initial onboarding (State 1)
        // Only show if they haven't dismissed step 1 yet (step is 0)
        $teamCreatedRecently =
            $team->created_at->diffInMinutes(now()) < 60 * 24; // Expand to 24 hours for flexibility
        $studentCount = Student::where("team_id", $team->id)->count();

        if ($currentStep === 0 && $teamCreatedRecently && $studentCount === 0) {
            return 1;
        }

        // Check for second step onboarding (State 2)
        // Show if they haven't dismissed step 2 yet (step <= 1) AND have reached the student threshold
        if (
            $currentStep <= 1 &&
            $studentCount >= self::ONBOARDING_STUDENT_THRESHOLD
        ) {
            // Optional: Add check if they have any activities yet?
            // $hasActivities = Activity::where('team_id', $team->id)->exists();
            // if (!$hasActivities) {
            return 2;
            // }
        }

        // Default: No onboarding needed for now
        return 0;
    }
    /**
     * Livewire action to mark an onboarding step as seen/completed.
     */
    public function markOnboardingStepComplete(int $step): void
    {
        $team = Auth::user()->currentTeam;

        if ($team && $step > $team->onboarding_step) {
            $team->update(["onboarding_step" => $step]);
            // Optionally force a refresh or re-render if needed, but closing the modal is usually enough.
            // $this->js('window.location.reload()'); // Force reload if state isn't updating visually
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
        $onboardingState = $this->getOnboardingState();
        $currentTeam = Auth::user()->currentTeam;

        return [
            "heading" => $this->getHeading(),
            "availableModels" => PrismServer::prisms()->pluck("name"),
            "availableStyles" => $this->getAvailableStyles(),
            "quickActions" => $this->getQuickActions(),
            "recentChats" => $this->getRecentChats(),
            "conversationId" => request()->get("conversation_id"),
            // 'needsOnboarding' => $this->needsOnboarding(), // Remove old flag
            "onboardingState" => $onboardingState, // Pass the state
            "studentResourceUrl" => $currentTeam
                ? route("filament.app.resources.students.create", [
                    "tenant" => $currentTeam,
                ])
                : "#",
            // Add URL for creating activities - Make sure ActivityResource exists and has a 'create' page
            "activityResourceCreateUrl" => $currentTeam
                ? ActivityResource::getUrl("create", ["tenant" => $currentTeam])
                : "#",
            "studentThreshold" => self::ONBOARDING_STUDENT_THRESHOLD, // Pass threshold for display if needed
        ];
    }
}
