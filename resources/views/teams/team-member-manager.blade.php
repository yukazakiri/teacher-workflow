@php
    use Illuminate\Support\Facades\Gate;
    use Illuminate\Support\Str;
@endphp

<div>
    <!-- Current Team Members Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Class Members') }}</h3>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    {{ $team->users->count() }} {{ Str::plural('Member', $team->users->count()) }}
                </span>
            </div>
        </div>

        @if($team->users->isNotEmpty())
            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($team->users->sortBy('name') as $user)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img class="h-10 w-10 rounded-full object-cover"
                                src="{{ $user->profile_photo_url }}"
                                alt="{{ $user->name }}" />

                            <div>
                                <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if (Laravel\Jetstream\Jetstream::hasRoles())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $user->membership->role === 'admin' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                    {{ Laravel\Jetstream\Jetstream::findRole($user->membership->role)->name }}
                                </span>

                                @if (Gate::check('updateTeamMember', $team))
                                    <button
                                        type="button"
                                        class="inline-flex items-center p-1 border border-transparent rounded-full text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none focus:text-gray-700 focus:bg-gray-100 dark:focus:bg-gray-800 transition"
                                        title="{{ __('Change Role') }}"
                                        wire:click="manageRole('{{ $user->id }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                @endif
                            @endif

                            <div class="flex items-center">
                                @if ($this->user->id === $user->id)
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        wire:click="$toggle('confirmingLeavingTeam')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('Leave') }}
                                    </button>
                                @elseif (Gate::check('removeTeamMember', $team))
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        wire:click="confirmTeamMemberRemoval('{{ $user->id }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('Remove') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-6 px-4 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('No Class Members found') }}</p>
            </div>
        @endif
    </div>

    <!-- Pending Invitations Card -->
    @if ($team->teamInvitations->isNotEmpty() && Gate::check('addTeamMember', $team))
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Pending Invitations') }}</h3>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        {{ $team->teamInvitations->count() }} {{ Str::plural('Invitation', $team->teamInvitations->count()) }}
                    </span>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($team->teamInvitations as $invitation)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $invitation->email }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Invited') }} {{ $invitation->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        @if (Gate::check('removeTeamMember', $team))
                            <button
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                wire:click="cancelTeamInvitation({{ $invitation->id }})">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Cancel Invitation') }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (Gate::check('addTeamMember', $team))
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Invite New Class Member') }}</h3>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <form wire:submit="addTeamMember" class="px-4 py-5 sm:px-6">
                <div class="grid gap-6">
                    <div>
                        <x-label for="email" value="{{ __('Email Address') }}" />
                        <x-input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            placeholder="colleague@example.com"
                            wire:model="addTeamMemberForm.email" />
                        <x-input-error for="email" class="mt-2" />
                    </div>

                    @if (count($this->roles) > 0)
                        <div>
                            <x-label value="{{ __('Role') }}" />
                            <x-input-error for="role" class="mt-2" />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                                @foreach ($this->roles as $role)
                                    <label
                                        for="role_{{ $role->key }}"
                                        class="relative border rounded-lg p-4 flex cursor-pointer {{ $addTeamMemberForm['role'] == $role->key ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 dark:border-indigo-500' : 'border-gray-300 dark:border-gray-600' }}">
                                        <input
                                            type="radio"
                                            id="role_{{ $role->key }}"
                                            name="role"
                                            value="{{ $role->key }}"
                                            wire:model="addTeamMemberForm.role"
                                            class="sr-only" />

                                        <div class="flex flex-col">
                                            <div class="flex items-center">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $role->name }}
                                                </span>

                                                @if ($addTeamMemberForm['role'] == $role->key)
                                                    <svg class="ml-2 h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                {{ $role->description }}
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <x-button wire:loading.attr="disabled" class="ml-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                            </svg>
                            {{ __('Send Invitation') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Role Management Modal -->
    <x-dialog-modal wire:model.live="currentlyManagingRole">
        <x-slot name="title">
            {{ __('Manage Class Member Role') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Please select the appropriate role for this Class member.') }}
                </p>

                <div class="grid gap-3">
                    @foreach ($this->roles as $role)
                        <label
                            for="modal_role_{{ $role->key }}"
                            class="relative border rounded-lg p-4 flex cursor-pointer {{ $currentRole == $role->key ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 dark:border-indigo-500' : 'border-gray-300 dark:border-gray-600' }}">
                            <input
                                type="radio"
                                id="modal_role_{{ $role->key }}"
                                name="modal_role"
                                value="{{ $role->key }}"
                                wire:model="currentRole"
                                class="sr-only" />

                            <div class="flex flex-col">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $role->name }}
                                    </span>

                                    @if ($currentRole == $role->key)
                                        <svg class="ml-2 h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    {{ $role->description }}
                                </p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="stopManagingRole" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ml-3" wire:click="updateRole" wire:loading.attr="disabled">
                {{ __('Save') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Leave Team Confirmation Modal -->
    <x-confirmation-modal wire:model.live="confirmingLeavingTeam">
        <x-slot name="title">
            {{ __('Leave Class') }}
        </x-slot>

        <x-slot name="content">
            <div class="flex items-center mb-4">
                <svg class="h-6 w-6 text-yellow-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ __('Are you sure you want to leave this Class?') }}
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('You will no longer have access to this class\'s resources.') }}
            </p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingLeavingTeam')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="leaveTeam" wire:loading.attr="disabled">
                {{ __('Leave class') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Remove Team Member Confirmation Modal -->
    <x-confirmation-modal wire:model.live="confirmingTeamMemberRemoval">
        <x-slot name="title">
            {{ __('Remove Class Member') }}
        </x-slot>

        <x-slot name="content">
            <div class="flex items-center mb-4">
                <svg class="h-6 w-6 text-yellow-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ __('Are you sure you want to remove this person from the class?') }}
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('They will no longer have access to this class\'s resources.') }}
            </p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingTeamMemberRemoval')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="removeTeamMember" wire:loading.attr="disabled">
                {{ __('Remove Member') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
