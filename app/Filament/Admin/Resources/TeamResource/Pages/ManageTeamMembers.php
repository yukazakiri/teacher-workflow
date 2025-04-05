<?php

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Mail;

class ManageTeamMembers extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TeamResource::class;

    protected static string $view = 'filament.admin.resources.team-resource.pages.manage-team-members';

    protected static ?string $title = 'Manage Team Members';

    public function getSubheading(): ?string
    {
        return 'Team: '.$this->record->name;
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('inviteMember')
                ->label('Invite New Member')
                ->icon('heroicon-o-envelope')
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required(),
                    Forms\Components\Select::make('role')
                        ->label('Team Role')
                        ->options([
                            'admin' => 'Admin',
                            'editor' => 'Editor',
                            'member' => 'Member',
                        ])
                        ->default('member')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Here you would create a team invitation
                    $this->record->teamInvitations()->create([
                        'email' => $data['email'],
                        'role' => $data['role'],
                    ]);

                    // In a real application, you would send an email here
                    // Mail::to($data['email'])->send(new TeamInvitation($this->record, $data['role']));

                    Notification::make()
                        ->title('Invitation sent successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('addExistingUser')
                ->label('Add Existing User')
                ->icon('heroicon-o-user-plus')
                ->form([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->options(User::all()->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('role')
                        ->label('Team Role')
                        ->options([
                            'admin' => 'Admin',
                            'editor' => 'Editor',
                            'member' => 'Member',
                        ])
                        ->default('member')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Add the user to the team
                    $this->record
                        ->users()
                        ->attach($data['user_id'], ['role' => $data['role']]);

                    Notification::make()
                        ->title('User added to team')
                        ->success()
                        ->send();
                }),
        ];
    }
}
