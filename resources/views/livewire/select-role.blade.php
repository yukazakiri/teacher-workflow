<div
    x-data="{ selectedRole: @entangle('selectedRole').live }"
    class="fixed inset-0 z-50 overflow-y-auto p-4 pt-[10vh] sm:pt-0 flex items-start sm:items-center justify-center bg-gray-900/50 dark:bg-gray-900/75"
>
    <div 
        class="w-full max-w-xl bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden flex flex-col max-h-[85vh]"
        x-trap.inert.noscroll="true"
    >
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-shrink-0">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                <x-heroicon-o-user-circle class="h-6 w-6 text-primary-500" />
                <span>Select Your Role</span>
            </h3>
        </div>

        <!-- Modal Body -->
        <div class="px-6 py-6 overflow-y-auto space-y-6">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 p-3 bg-primary-100 dark:bg-primary-500/20 rounded-full">
                    <x-heroicon-o-user-group class="h-10 w-10 text-primary-600 dark:text-primary-400" />
                </div>
                <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Welcome to the Class!</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Please select your role to continue.</p>
            </div>

            <form wire:submit="setRole" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    @foreach($roles as $role)
                        <label 
                            for="role-{{ $role->key }}" 
                            class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm transition-all duration-200"
                            :class="selectedRole === '{{ $role->key }}' ? 
                                'border-primary-500 ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-800/20 dark:border-primary-400' : 
                                'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-200 dark:hover:border-primary-800'"
                        >
                            <input 
                                type="radio" 
                                name="selectedRole" 
                                id="role-{{ $role->key }}" 
                                value="{{ $role->key }}"
                                wire:model.live="selectedRole"
                                class="sr-only"
                            >
                            <div class="flex flex-1 items-center space-x-4">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full"
                                    :class="selectedRole === '{{ $role->key }}' ? 
                                        'bg-primary-100 dark:bg-primary-800/30 text-primary-600 dark:text-primary-400' :
                                        'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'"
                                >
                                    @if($role->key === 'student')
                                        <x-heroicon-o-academic-cap class="h-6 w-6" />
                                    @elseif($role->key === 'parent')
                                        <x-heroicon-o-home-modern class="h-6 w-6" />
                                    @endif
                                </div>
                                <div class="flex-grow">
                                    <p 
                                        class="font-medium"
                                        :class="selectedRole === '{{ $role->key }}' ? 
                                            'text-primary-700 dark:text-primary-300' : 
                                            'text-gray-900 dark:text-white'"
                                    >{{ $role->name }}</p>
                                    <p 
                                        class="text-sm"
                                        :class="selectedRole === '{{ $role->key }}' ? 
                                            'text-primary-600 dark:text-primary-400' : 
                                            'text-gray-500 dark:text-gray-400'"
                                    >{{ $role->description }}</p>
                                </div>
                                <div class="ml-auto flex h-5 items-center">
                                    <div 
                                        class="flex h-5 w-5 items-center justify-center rounded-full border-2"
                                        :class="selectedRole === '{{ $role->key }}' ? 
                                            'border-primary-500 bg-primary-500 dark:border-primary-400' : 
                                            'border-gray-300 dark:border-gray-600'"
                                    >
                                        <div 
                                            class="h-2 w-2 rounded-full bg-white" 
                                            x-show="selectedRole === '{{ $role->key }}'"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="mt-6">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        size="lg"
                        class="w-full justify-center"
                    >
                        Continue with {{ ucfirst($selectedRole) }} Role
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</div>
