<?php

namespace App\Filament\Pages;

use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as PagesDashboard;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\TeamInvitation;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Facades\FilamentIcon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Livewire;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\TeamMembersTableWidget;
use App\Filament\Widgets\PendingInvitationsTableWidget;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class Dashboard extends PagesDashboard
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Home';
    /**
     * @var view-string
     */
    protected static string $view = 'filament.pages.dashboard';

   

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-home' : 'heroicon-o-home');
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
        return 2;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }

   

    protected function getViewData(): array
    {
        $user = Auth::user();
        $currentTeam = $user->currentTeam;

        // Get all teams the user belongs to
        $allTeams = Team::whereHas('users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->get();

        // Get teams owned by the user
        $ownedTeams = Team::where('user_id', $user->id)->get();

        // Get teams the user is a member of but doesn't own
        $joinedTeams = $allTeams->filter(function ($team) use ($user) {
            return $team->user_id !== $user->id;
        });

        // Combine all teams and mark if user is owner
        $teams = $allTeams->map(function ($team) use ($user) {
            $team->isOwner = $team->user_id === $user->id;
            return $team;
        });

        // Team stats
        $stats = [
            'memberCount' => $currentTeam->users->count(),
            'pendingInvites' => TeamInvitation::where('team_id', $currentTeam->id)->count(),
            'isOwner' => $currentTeam->user_id === $user->id,
            'createdAt' => $currentTeam->created_at->diffForHumans()
        ];

        // Get user's role in current team
        $userRole = 'Member';
        $pivotRole = DB::table('team_user')
            ->where('team_id', $currentTeam->id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($pivotRole === 'admin') {
            $userRole = 'Admin';
        } elseif ($pivotRole === 'editor') {
            $userRole = 'Editor';
        }

        if ($currentTeam->user_id === $user->id) {
            $userRole = 'Owner';
        }

        // Get team statistics for each team
        $teams->each(function ($team) use ($user) {
            $team->memberCount = $team->users->count();
            $team->pendingInvites = TeamInvitation::where('team_id', $team->id)->count();
            
            // Get the user's role in this team
            $team->userRole = 'Member';
            $pivotRole = DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->value('role');

            if ($pivotRole === 'admin') {
                $team->userRole = 'Admin';
            } elseif ($pivotRole === 'editor') {
                $team->userRole = 'Editor';
            }

            if ($team->user_id === $user->id) {
                $team->userRole = 'Owner';
            }
            
            // Get join date for the user
            if (!$team->isOwner) {
                $joinDate = $team->users->find($user->id)->pivot->created_at ?? now();
                $team->joinedAt = $joinDate->diffForHumans();
            }
        });

        return [
            'currentTeam' => $currentTeam,
            'teams' => $teams,
            'ownedTeams' => $ownedTeams,
            'joinedTeams' => $joinedTeams,
            'stats' => $stats,
            'userRole' => $userRole,
            'hasTeamFeatures' => \Laravel\Jetstream\Jetstream::hasTeamFeatures(),
        ];
    }
}
