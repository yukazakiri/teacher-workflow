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

class Dashboard extends PagesDashboard
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Home';
    /**
     * @var view-string
     */
    protected static string $view = 'filament.pages.dashboard';

    // Add support for quick actions
   

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-chat-bubble-oval-left-ellipsis' : 'heroicon-o-chat-bubble-oval-left-ellipsis');
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
    public function getColumns(): int | string | array
    {
        return 1; // Set to 1 to match the Claude interface's centered layout
    }

    public function getTitle(): string | Htmlable
    {
        // Personalized title
        return 'Welcome, ' . auth()->user()->name;
    }

    /**
     * Check if the current team has any students.
     */
    public function hasStudents(): bool
    {
        if (!Auth::user()->currentTeam) {
            return false;
        }

        return Student::where('team_id', Auth::user()->currentTeam->id)->exists();
    }

    /**
     * Check if the user needs onboarding.
     */
    public function needsOnboarding(): bool
    {
        // If the team was recently created (within the last hour) and has no students
        if (Auth::user()->currentTeam) {
            $teamCreatedRecently = Auth::user()->currentTeam->created_at->diffInHours(now()) < 1;
            return $teamCreatedRecently && !$this->hasStudents();
        }
        
        return false;
    }

    /**
     * Get the available AI models.
     */
   

    /**
     * Get the available chat styles.
     */
    public function getAvailableStyles(): array
    {
        $chatService = app(PrismChatService::class);
        return $chatService->getAvailableStyles();
    }

    /**
     * Get the quick actions for the chat.
     */
    public function getQuickActions(): array
    {
        return [
            [
                'name' => 'Polish prose',
                'description' => 'Improve writing style and clarity',
                'prompt' => 'Please polish the following text to improve its clarity, style, and professionalism: ',
            ],
            [
                'name' => 'Generate questions',
                'description' => 'Create discussion questions for students',
                'prompt' => 'Generate 5 thought-provoking discussion questions for students about the following topic: ',
            ],
            [
                'name' => 'Write a memo',
                'description' => 'Create a professional memo',
                'prompt' => 'Write a professional memo about the following topic: ',
            ],
            [
                'name' => 'Summarize',
                'description' => 'Create a concise summary',
                'prompt' => 'Please summarize the following text in a clear and concise manner: ',
            ],
        ];
    }

    /**
     * Get recent chats for the current user.
     */
    public function getRecentChats(): array
    {
        $recentChats = \App\Models\Conversation::where('user_id', Auth::id())
            ->orderBy('last_activity_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->truncated_title,
                    'model' => $chat->model,
                    'last_activity' => $chat->last_activity_at->diffForHumans(),
                    'avatar_letter' => strtoupper(substr($chat->title, 0, 1)),
                    'color' => $this->getRandomColor(),
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
        $colors = ['primary', 'success', 'warning', 'danger', 'info'];
        return $colors[array_rand($colors)];
    }

    /**
     * Get data for the view.
     */
    protected function getViewData(): array
    {
        return [
            'heading' => $this->getHeading(),
            'availableModels' => PrismServer::prisms()->pluck('name'),
            'availableStyles' => $this->getAvailableStyles(),
            'quickActions' => $this->getQuickActions(),
            'recentChats' => $this->getRecentChats(),
            'conversationId' => request()->get('conversation_id'),
            'needsOnboarding' => $this->needsOnboarding(),
            'studentResourceUrl' => route('filament.app.resources.students.create', ['tenant' => Auth::user()->currentTeam]),
        ];
    }
}
