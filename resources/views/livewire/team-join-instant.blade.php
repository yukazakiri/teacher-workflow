<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <h1 class="text-2xl font-semibold mb-6 flex items-center">
                    <x-heroicon-o-user-group class="w-8 h-8 mr-2 text-primary-500" />
                    Join Team
                </h1>

                @if($isLoading)
                    <div class="flex justify-center items-center p-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
                    </div>
                @elseif($joinSuccess)
                    <div class="bg-success-100 dark:bg-success-900/20 border border-success-200 dark:border-success-700 rounded-lg p-6 text-center mb-6">
                        <x-heroicon-o-check-circle class="w-16 h-16 text-success-500 mx-auto mb-4" />
                        <h2 class="text-xl font-medium text-success-700 dark:text-success-400 mb-2">Successfully Joined Team!</h2>
                        <p class="text-success-600 dark:text-success-300 mb-4">
                            You are now a member of {{ $team->name }}.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <x-heroicon-o-home class="w-5 h-5 mr-2" /> Go to Dashboard
                            </a>
                        </div>
                    </div>
                @elseif(!$isValid)
                    <div class="bg-danger-100 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-700 rounded-lg p-6 text-center mb-6">
                        <x-heroicon-o-exclamation-triangle class="w-16 h-16 text-danger-500 mx-auto mb-4" />
                        <h2 class="text-xl font-medium text-danger-700 dark:text-danger-400 mb-2">
                            @if($isExpired)
                                Invitation Expired
                            @elseif($isUsed)
                                Invitation No Longer Available
                            @elseif($isAlreadyMember)
                                Already a Member
                            @else
                                Invalid Invitation
                            @endif
                        </h2>
                        <p class="text-danger-600 dark:text-danger-300 mb-4">
                            {{ $errorMessage }}
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <x-heroicon-o-home class="w-5 h-5 mr-2" /> Go to Dashboard
                            </a>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
                        <div class="flex flex-col md:flex-row gap-6 items-center">
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-full p-4">
                                <x-heroicon-o-user-group class="w-16 h-16 text-primary-500" />
                            </div>
                            <div>
                                <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                                    Join "{{ $team->name }}"
                                </h2>
                                <p class="text-gray-600 dark:text-gray-300 mb-2">
                                    You've been invited to join this team. Join now to access shared resources and collaborate with other members.
                                </p>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <p>Team Owner: {{ $team->owner->name }}</p>
                                    <p>Invitation expires: {{ $qrCode->expires_at->format('M j, Y g:i A') }} ({{ $qrCode->expires_at->diffForHumans() }})</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-center">
                            <x-filament::button
                                size="lg"
                                color="primary"
                                wire:click="joinTeam"
                                wire:loading.attr="disabled"
                                wire:target="joinTeam"
                            >
                                <x-heroicon-m-user-plus class="w-5 h-5 mr-2" />
                                Join Team Now
                                <x-filament::loading-indicator wire:loading wire:target="joinTeam" class="h-5 w-5 ml-2" />
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div> 