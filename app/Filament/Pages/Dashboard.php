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

class Dashboard extends PagesDashboard
{

    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    /**
     * @var view-string
     */
    // protected static string $view = 'filament-panels::pages.dashboard';

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
     protected static string $view = 'filament.pages.dashboard';
    // public function getHeader(): ?string
    // {
    //     return 'Team Dashboard';
    // }

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
                            return Auth::user()->allTeams()->pluck('name', 'id');
                        })
                        ->required(),
                        ])
                        ->action(function (array $data): void {
                            $team = Team::find($data['team_id']);

                            if ($team && Auth::user()->belongsToTeam($team)) {
                                // Use Jetstream's event system to switch teams
                                Auth::user()->switchTeam($team);

                                // Laravel will automatically fire the Jetstream\Events\TeamSwitched event
                                // which will handle team switching logic

                                Notification::make()
                                    ->title('Team Switched')
                                    ->body("You are now using the {$team->name} team.")
                                    ->success()
                                    ->send();

                                // Redirect to refresh the page with the new team context
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
                    $team = $user->ownedTeams()->create([
                        'name' => $data['name'],
                        'personal_team' => false,
                    ]);

                    $user->switchTeam($team);

                    Notification::make()
                        ->title('Team Created')
                        ->body("You have created the {$team->name} team.")
                        ->success()
                        ->send();

                    $this->redirect(route('filament.app.pages.dashboard'));
                }),
        ];
    }

    protected function getViewData(): array
        {
            $currentTeam = Auth::user()->currentTeam;
            $allTeams = Auth::user()->allTeams();

            // Get pending invitations for current team
            $pendingInvitations = TeamInvitation::where('team_id', $currentTeam->id)->get();

            // Get team members with their roles
            $teamMembers = $currentTeam->users->map(function ($user) use ($currentTeam) {
                $roleName = $user->teamRole($currentTeam)->name ?? 'Member';
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photo' => $user->profile_photo_url,
                    'role' => $roleName,
                    'isOwner' => $currentTeam->owner_id === $user->id,
                    'joinedAt' => $user->created_at->diffForHumans(),
                ];
            });

            // Get current user's upcoming tasks or activities (example)
            $userActivity = collect([
                // Here you could query user's tasks, recent activities, etc.
            ]);

            // Team stats
            $stats = [
                'memberCount' => $currentTeam->users->count(),
                'pendingInvites' => $pendingInvitations->count(),
                'isOwner' => Auth::user()->ownsTeam($currentTeam),
                'createdAt' => $currentTeam->created_at->diffForHumans()
            ];

            return [
                'currentTeam' => $currentTeam,
                'allTeams' => $allTeams,
                'teamMembers' => $teamMembers,
                'pendingInvitations' => $pendingInvitations,
                'stats' => $stats,
                'userRole' => Auth::user()->teamRole($currentTeam)->name ?? 'Member',
            ];
        }

    public function mount(): void
    {
        // parent::mount();
        $this->heading = 'Team Dashboard: ' . Auth::user()->currentTeam->name;
    }
}
