<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-4">
        <!-- Team Overview Card -->
        <div class="md:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Team Overview</x-slot>

                <div class="flex flex-col space-y-6">
                    <!-- Team Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center justify-center w-16 h-16 text-xl font-bold text-white rounded-full bg-primary-600">
                                {{ substr($currentTeam->name, 0, 2) }}
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">{{ $currentTeam->name }}</h3>
                                <p class="text-sm text-gray-500">
                                    Created {{ $currentTeam->created_at->diffForHumans() }}
                                    @if($currentTeam->personal_team)
                                        • Personal Team
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            @if(Auth::user()->ownsTeam($currentTeam))
                                <x-filament::badge color="success">
                                    Team Owner
                                </x-filament::badge>
                            @else
                                <x-filament::badge>
                                    {{ $userRole }}
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>

                    <!-- Team Stats -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <x-filament::card>
                            <div class="flex flex-col items-center justify-center h-24 text-center">
                                <span class="text-3xl font-bold text-primary-600">{{ $stats['memberCount'] }}</span>
                                <span class="text-sm text-gray-500">Team Members</span>
                            </div>
                        </x-filament::card>

                        <x-filament::card>
                            <div class="flex flex-col items-center justify-center h-24 text-center">
                                <span class="text-3xl font-bold text-amber-600">{{ $stats['pendingInvites'] }}</span>
                                <span class="text-sm text-gray-500">Pending Invites</span>
                            </div>
                        </x-filament::card>

                        <x-filament::card>
                            <div class="flex flex-col items-center justify-center h-24 text-center">
                                <span class="text-3xl font-bold text-emerald-600">{{ $allTeams->count() }}</span>
                                <span class="text-sm text-gray-500">Your Teams</span>
                            </div>
                        </x-filament::card>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Quick Actions -->
        <div class="md:col-span-1 lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Quick Actions</x-slot>

                <div class="grid grid-cols-2 gap-4">
                    <x-filament::button
                        tag="button"
                        wire:click="mountAction('invite_member')"
                        color="primary"
                        icon="heroicon-m-user-plus"
                        class="h-24 justify-center"
                        outlined
                    >
                        Invite Member
                    </x-filament::button>

                    <x-filament::button
                        tag="button"
                        wire:click="mountAction('switch_team')"
                        color="gray"
                        icon="heroicon-m-arrows-right-left"
                        class="h-24 justify-center"
                        outlined
                    >
                        Switch Team
                    </x-filament::button>

                    <x-filament::button
                        tag="button"
                        wire:click="mountAction('create_team')"
                        color="success"
                        icon="heroicon-m-plus"
                        class="h-24 justify-center"
                        outlined
                    >
                        Create Team
                    </x-filament::button>

                    @if($stats['isOwner'])
                    <x-filament::button
                        tag="a"
                        href="{{ route('teams.show', $currentTeam->id) }}"
                        target="_blank"
                        color="info"
                        icon="heroicon-m-cog-6-tooth"
                        class="h-24 justify-center"
                        outlined
                    >
                        Team Settings
                    </x-filament::button>
                    @endif
                </div>
            </x-filament::section>
        </div>

        <!-- Team Members -->
        <div class="md:col-span-2 lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Team Members</x-slot>
                <x-slot name="headerActions">
                    @if($stats['isOwner'])
                        <x-filament::button
                            size="sm"
                            color="primary"
                            wire:click="mountAction('invite_member')"
                            icon="heroicon-m-user-plus"
                        >
                            Invite
                        </x-filament::button>
                    @endif
                </x-slot>

                <div class="space-y-2">
                    @forelse($teamMembers as $member)
                        <div class="flex items-center justify-between p-3 transition bg-gray-50 rounded-lg hover:bg-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    @if($member['photo'])
                                        <img class="object-cover w-10 h-10 rounded-full" src="{{ $member['photo'] }}" alt="{{ $member['name'] }}">
                                    @else
                                        <div class="flex items-center justify-center w-10 h-10 text-sm font-medium text-white rounded-full bg-primary-600">
                                            {{ substr($member['name'], 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium">
                                        {{ $member['name'] }}
                                        @if($member['isOwner'])
                                            <x-filament::badge size="sm" color="success">Owner</x-filament::badge>
                                        @endif
                                    </h4>
                                    <p class="text-xs text-gray-500">{{ $member['email'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="text-xs font-medium text-gray-500">{{ $member['role'] }}</span>

                                @if($stats['isOwner'] && !$member['isOwner'] && $member['id'] !== auth()->id())
                                    <x-filament::icon-button
                                        icon="heroicon-m-ellipsis-vertical"
                                        label="Options"
                                        color="gray"
                                    />
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <p class="text-sm text-gray-500">No team members found.</p>
                        </div>
                    @endforelse
                </div>

                @if($teamMembers->count() > 5)
                    <div class="pt-3 mt-3 text-center border-t">
                        <x-filament::button color="gray" size="sm">
                            View all members
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <!-- All Your Teams -->
        <div class="md:col-span-1 lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Your Teams</x-slot>
                <x-slot name="headerActions">
                    <x-filament::button
                        size="sm"
                        color="success"
                        wire:click="mountAction('create_team')"
                        icon="heroicon-m-plus"
                    >
                        New
                    </x-filament::button>
                </x-slot>

                <div class="space-y-2">
                    @forelse($allTeams as $team)
                        <div class="flex items-center justify-between p-3 transition rounded-lg {{ $team->id === $currentTeam->id ? 'bg-primary-50' : 'bg-gray-50 hover:bg-gray-100' }}">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 text-xs font-medium text-white rounded-full {{ $team->id === $currentTeam->id ? 'bg-primary-600' : 'bg-gray-600' }}">
                                    {{ substr($team->name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium">{{ $team->name }}</h4>
                                    <p class="text-xs text-gray-500">
                                        {{ $team->users->count() }} {{ $team->users->count() === 1 ? 'member' : 'members' }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                @if($team->id === $currentTeam->id)
                                    <x-filament::badge color="primary" size="sm">
                                        Current
                                    </x-filament::badge>
                                @else
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        wire:click="mountAction('switch_team', {'team_id': {{ $team->id }}})"
                                    >
                                        Switch
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <p class="text-sm text-gray-500">You don't belong to any teams yet.</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <!-- Pending Invitations -->
        @if($stats['isOwner'] && $pendingInvitations->count() > 0)
        <div class="md:col-span-3 lg:col-span-4">
            <x-filament::section>
                <x-slot name="heading">Pending Invitations</x-slot>
                <x-slot name="description">
                    These people have been invited to your team but haven't joined yet.
                </x-slot>

                <div class="overflow-hidden bg-white shadow sm:rounded-md">
                    <ul role="list" class="divide-y divide-gray-200">
                        @foreach($pendingInvitations as $invitation)
                            <li class="flex items-center justify-between px-4 py-3 transition hover:bg-gray-50">
                                <div class="flex items-center min-w-0 gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 text-sm font-medium text-white rounded-full bg-amber-500">
                                        {{ substr($invitation->email, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ $invitation->email }}</p>
                                        <p class="text-xs text-gray-500">
                                            Invited as {{ ucfirst($invitation->role) }}
                                            • {{ $invitation->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        tag="a"
                                        href="mailto:{{ $invitation->email }}"
                                    >
                                        Remind
                                    </x-filament::button>

                                    <form action="{{ route('team-invitations.destroy', $invitation->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-filament::button
                                            type="submit"
                                            size="sm"
                                            color="danger"
                                            onclick="return confirm('Are you sure you want to cancel this invitation?')"
                                        >
                                            Cancel
                                        </x-filament::button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-filament::section>
        </div>
        @endif
    </div>
</x-filament-panels::page>
