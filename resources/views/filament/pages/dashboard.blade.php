<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Current Team Card -->
        <div class="lg:col-span-3">
            <div class="overflow-hidden bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="relative bg-gradient-to-r from-primary-600 to-primary-500 p-6 text-white">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center justify-center w-16 h-16 text-xl font-bold rounded-full bg-white/20 backdrop-blur-sm">
                                {{ substr($currentTeam->name, 0, 2) }}
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold">{{ $currentTeam->name }}</h2>
                                <p class="text-sm text-white/80">
                                    Created {{ $currentTeam->created_at->diffForHumans() }}
                                    @if($currentTeam->personal_team)
                                        • Personal Team
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div>
                            @if($stats['isOwner'])
                                <div class="px-3 py-1 text-xs font-medium rounded-full bg-white/20 backdrop-blur-sm">
                                    Team Owner
                                </div>
                            @else
                                <div class="px-3 py-1 text-xs font-medium rounded-full bg-white/20 backdrop-blur-sm">
                                    {{ $userRole }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="p-4 transition bg-gray-50 rounded-lg dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 text-primary-600 bg-primary-100 rounded-lg dark:bg-primary-900/50 dark:text-primary-400">
                                        <x-filament::icon icon="heroicon-o-user-group" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Members</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['memberCount'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 transition bg-gray-50 rounded-lg dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 text-amber-600 bg-amber-100 rounded-lg dark:bg-amber-900/50 dark:text-amber-400">
                                        <x-filament::icon icon="heroicon-o-envelope" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Invites</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pendingInvites'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 transition bg-gray-50 rounded-lg dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 text-emerald-600 bg-emerald-100 rounded-lg dark:bg-emerald-900/50 dark:text-emerald-400">
                                        <x-filament::icon icon="heroicon-o-building-office-2" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Teams</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $allTeams->count() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 mt-6">
                        <x-filament::button
                            wire:click="mountAction('invite_member')"
                            color="primary"
                            icon="heroicon-m-user-plus"
                        >
                            Invite Member
                        </x-filament::button>

                        <x-filament::button
                            wire:click="mountAction('switch_team')"
                            color="gray"
                            icon="heroicon-m-arrows-right-left"
                        >
                            Switch Team
                        </x-filament::button>

                        <x-filament::button
                            wire:click="mountAction('create_team')"
                            color="success"
                            icon="heroicon-m-plus"
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
                        >
                            Team Settings
                        </x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Teams You Own -->
        <div class="lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-building-library" class="w-5 h-5 text-primary-500" />
                        <span>Teams You Own</span>
                    </div>
                </x-slot>

                <x-slot name="headerActions">
                    <x-filament::button
                        size="sm"
                        color="success"
                        wire:click="mountAction('create_team')"
                        icon="heroicon-m-plus"
                    >
                        New Team
                    </x-filament::button>
                </x-slot>

                <div class="space-y-4">
                    @if($ownedTeams->isEmpty())
                        <div class="flex flex-col items-center justify-center p-6 text-center">
                            <div class="p-3 mb-4 text-primary-500 bg-primary-100 rounded-full dark:bg-primary-900/50">
                                <x-filament::icon icon="heroicon-o-building-office-2" class="w-6 h-6" />
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">No Teams Created Yet</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Create your first team to start collaborating with others.
                            </p>
                            <x-filament::button
                                wire:click="mountAction('create_team')"
                                color="primary"
                                icon="heroicon-m-plus"
                                class="mt-4"
                            >
                                Create Team
                            </x-filament::button>
                        </div>
                    @else
                        @foreach($ownedTeams as $team)
                            <div class="relative overflow-hidden transition-all duration-300 bg-white border rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md group">
                                @if($team->id === $currentTeam->id)
                                    <div class="absolute top-0 right-0 px-2 py-1 text-xs font-medium text-white bg-primary-500 rounded-bl-lg">
                                        Current
                                    </div>
                                @endif

                                <div class="p-4">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center justify-center w-12 h-12 text-lg font-bold text-white rounded-lg {{ $team->id === $currentTeam->id ? 'bg-primary-600' : 'bg-gray-600' }}">
                                            {{ substr($team->name, 0, 2) }}
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-base font-semibold text-gray-900 truncate dark:text-white">
                                                {{ $team->name }}
                                            </h3>
                                            <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ $team->users->count() }} {{ $team->users->count() === 1 ? 'member' : 'members' }}</span>
                                                <span class="mx-1.5">•</span>
                                                <span>Created {{ $team->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>

                                        @if($team->id !== $currentTeam->id)
                                            <x-filament::button
                                                size="sm"
                                                color="gray"
                                                wire:click="mountAction('switch_team', {'team_id': {{ $team->id }}})"
                                            >
                                                Switch
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </x-filament::section>

            <!-- Team Members -->
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-user-group" class="w-5 h-5 text-primary-500" />
                        <span>Team Members</span>
                    </div>
                </x-slot>

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

                <div class="space-y-3">
                    @forelse($teamMembers as $member)
                        <div class="flex items-center justify-between p-3 transition rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/60">
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
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $member['name'] }}
                                        @if($member['isOwner'])
                                            <x-filament::badge size="sm" color="success">Owner</x-filament::badge>
                                        @endif
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member['email'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $member['role'] }}</span>

                                @if($stats['isOwner'] && !$member['isOwner'] && $member['id'] !== auth()->id())
                                    <x-filament::icon-button
                                        icon="heroicon-m-ellipsis-vertical"
                                        label="Options"
                                        color="gray"
                                        class="ml-3"
                                    />
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No team members found.</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <!-- Teams You've Joined -->
        <div class="lg:col-span-1">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-user-circle" class="w-5 h-5 text-primary-500" />
                        <span>Teams You've Joined</span>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    @if($joinedTeams->isEmpty())
                        <div class="flex flex-col items-center justify-center p-6 text-center">
                            <div class="p-3 mb-4 text-gray-400 bg-gray-100 rounded-full dark:bg-gray-700">
                                <x-filament::icon icon="heroicon-o-user-group" class="w-6 h-6" />
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">No Joined Teams</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                You haven't joined any teams yet.
                            </p>
                        </div>
                    @else
                        @foreach($joinedTeams as $team)
                            <div class="relative overflow-hidden transition-all duration-300 bg-white border rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md group">
                                @if($team->id === $currentTeam->id)
                                    <div class="absolute top-0 right-0 px-2 py-1 text-xs font-medium text-white bg-primary-500 rounded-bl-lg">
                                        Current
                                    </div>
                                @endif

                                <div class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-lg {{ $team->id === $currentTeam->id ? 'bg-primary-600' : 'bg-gray-600' }}">
                                            {{ substr($team->name, 0, 2) }}
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-semibold text-gray-900 truncate dark:text-white">
                                                {{ $team->name }}
                                            </h3>
                                            <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ $team->users->count() }} {{ $team->users->count() === 1 ? 'member' : 'members' }}</span>
                                            </div>
                                        </div>

                                        @if($team->id !== $currentTeam->id)
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
                            </div>
                        @endforeach
                    @endif
                </div>
            </x-filament::section>

            <!-- Pending Invitations (Conditional) -->
            @if($stats['isOwner'] && $pendingInvitations->count() > 0)
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-envelope" class="w-5 h-5 text-amber-500" />
                        <span>Pending Invitations</span>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @foreach($pendingInvitations as $invitation)
                        <div class="flex items-center justify-between p-3 transition rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/60">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 text-sm font-medium text-white rounded-full bg-amber-500">
                                    {{ substr($invitation->email, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $invitation->email }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Invited as {{ ucfirst($invitation->role) }}
                                        • {{ $invitation->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::button
                                    size="xs"
                                    color="danger"
                                    icon="heroicon-m-trash"
                                >
                                    Cancel
                                </x-filament::button>

                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    icon="heroicon-m-paper-airplane"
                                >
                                    Resend
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
