<x-filament::page>
    <x-filament::section>
        <h2 class="text-xl font-bold mb-4">Current Team Members</h2>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($this->record->users as $user)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-800 flex items-center justify-center text-primary-700 dark:text-primary-300">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $user->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $user->email }}
                            </p>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->membership->role === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($user->membership->role === 'editor' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200') }}">
                                    {{ ucfirst($user->membership->role ?? 'member') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex space-x-2">
                            <button type="button" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <span class="sr-only">Edit</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button type="button" class="text-red-400 hover:text-red-500 dark:hover:text-red-300">
                                <span class="sr-only">Remove</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <h2 class="text-xl font-bold mb-4">Pending Invitations</h2>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($this->record->teamInvitations as $invitation)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-800 flex items-center justify-center text-yellow-700 dark:text-yellow-300">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $invitation->email }}
                            </p>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invitation->role === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($invitation->role === 'editor' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200') }}">
                                    {{ ucfirst($invitation->role) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Invited {{ $invitation->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 flex space-x-2">
                            <button type="button" class="text-red-400 hover:text-red-500 dark:hover:text-red-300">
                                <span class="sr-only">Cancel</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                @if($this->record->teamInvitations->isEmpty())
                    <div class="col-span-3 text-center py-4 text-gray-500 dark:text-gray-400">
                        No pending invitations
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament::page> 