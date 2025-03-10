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

class Dashboard extends PagesDashboard
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    /**
     * @var view-string
     */
    protected static string $view = 'filament.pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ??
            static::$title ??
            __('filament-panels::pages/dashboard.title');
    }

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
        return Filament::getWidgets();
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switch_team')
                ->label('Switch Team')
                ->icon('heroicon-o-arrow-path')
                ->iconPosition(IconPosition::Before)
                ->color('primary')
                ->form([
                    Select::make('team_id')
                        ->label('Select Team')
                        ->options(function () {
                            return Auth::user()->teams->pluck('name', 'id');
                        })
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $team = Team::find($data['team_id']);

                    if ($team && Auth::user()->belongsToTeam($team)) {
                        // Use the Jetstream method to switch teams
                        Auth::user()->switchTeam($team);

                        Notification::make()
                            ->title('Team Switched')
                            ->body("You are now using the {$team->name} team.")
                            ->success()
                            ->send();

                        $this->redirect(route('filament.app.pages.dashboard', ['tenant' => $team->id]));
                    }
                }),

            CreateAction::make('invite_member')
                ->label('Invite Team Member')
                ->icon('heroicon-o-user-plus')
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->form([
                    Livewire::make(TeamMemberManager::class, ['team' => Auth::user()->currentTeam])
                        ->key('Team')
                ])
                ->action(function (array $data): void {
                    $team = Auth::user()->currentTeam;

                    // Check if user already exists
                    $user = User::where('email', $data['email'])->first();

                    if ($user && $team->hasUser($user)) {
                        Notification::make()
                            ->title('User Already In Team')
                            ->body('This user is already a member of this team.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Create invitation
                    $invitation = TeamInvitation::create([
                        'team_id' => $team->id,
                        'email' => $data['email'],
                        'role' => $data['role'],
                    ]);

                    // Send invitation email
                    $invitation->sendInvitationNotification();

                    Notification::make()
                        ->title('Invitation Sent')
                        ->body('A team invitation has been sent to ' . $data['email'])
                        ->success()
                        ->send();
                }),

            Action::make('create_team')
                ->label('Create New Team')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    TextInput::make('name')
                        ->label('Team Name')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $user = Auth::user();

                    // Create a new team using Jetstream's API
                    $team = $user->ownedTeams()->create([
                        'name' => $data['name'],
                        'personal_team' => false,
                    ]);

                    // Switch to the newly created team
                    $user->switchTeam($team);

                    Notification::make()
                        ->title('Team Created')
                        ->body("You have created the {$team->name} team.")
                        ->success()
                        ->send();

                        $this->redirect(route('filament.app.pages.dashboard', ['tenant' => $team->id]));
                }),
        ];
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $currentTeam = $user->currentTeam;

        // Get all teams the user belongs to
        $allTeams = $user->allTeams();

        // Get teams owned by the user
        $ownedTeams = $user->ownedTeams;

        // Get teams the user is a member of but doesn't own
        $joinedTeams = $allTeams->filter(function ($team) use ($user) {
            return $team->user_id !== $user->id;
        });

        // Get pending invitations for current team
        $pendingInvitations = TeamInvitation::where('team_id', $currentTeam->id)->get();

        // Get team members with their roles
        $teamMembers = $currentTeam->users->map(function ($teamUser) use ($currentTeam) {
            // Get the role name using Jetstream's API
            $teamRole = null;

            if ($teamUser->hasTeamRole($currentTeam, 'admin')) {
                $teamRole = 'Admin';
            } elseif ($teamUser->hasTeamRole($currentTeam, 'editor')) {
                $teamRole = 'Editor';
            } else {
                $teamRole = 'Member';
            }

            // Check if user is the team owner
            $isOwner = $currentTeam->user_id === $teamUser->id;
            if ($isOwner) {
                $teamRole = 'Owner';
            }

            return [
                'id' => $teamUser->id,
                'name' => $teamUser->name,
                'email' => $teamUser->email,
                'photo' => $teamUser->profile_photo_url,
                'role' => $teamRole,
                'isOwner' => $isOwner,
                'joinedAt' => $teamUser->created_at->diffForHumans(),
            ];
        });

        // Team stats
        $stats = [
            'memberCount' => $currentTeam->users->count(),
            'pendingInvites' => $pendingInvitations->count(),
            'isOwner' => $currentTeam->user_id === $user->id,
            'createdAt' => $currentTeam->created_at->diffForHumans()
        ];

        // Get user's role in current team
        $userRole = 'Member';

        if ($user->hasTeamRole($currentTeam, 'admin')) {
            $userRole = 'Admin';
        } elseif ($user->hasTeamRole($currentTeam, 'editor')) {
            $userRole = 'Editor';
        }

        if ($currentTeam->user_id === $user->id) {
            $userRole = 'Owner';
        }

        return [
            'currentTeam' => $currentTeam,
            'allTeams' => $allTeams,
            'ownedTeams' => $ownedTeams,
            'joinedTeams' => $joinedTeams,
            'teamMembers' => $teamMembers,
            'pendingInvitations' => $pendingInvitations,
            'stats' => $stats,
            'userRole' => $userRole,
        ];
    }
}
