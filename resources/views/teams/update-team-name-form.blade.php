@php
    use Illuminate\Support\Facades\Gate;
@endphp

<div class="space-y-6">
    <div class="filament-card rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
                {{ __('Class Name') }}
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage class identity') }}</span>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            {{ __('The class\'s name and owner information.') }}
        </p>

        <form wire:submit="updateTeamName">
            <div class="space-y-6">
                <!-- Team Owner Information -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('Class Owner') }}</h3>

                    <div class="flex items-center">
                        <div class="relative">
                            <img class="h-16 w-16 rounded-full object-cover ring-2 ring-primary-500/30 shadow-sm"
                                src="{{ $team->owner->profile_photo_url }}"
                                alt="{{ $team->owner->name }}">
                            <div class="absolute -bottom-1 -right-1 bg-primary-500 text-white p-1 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <div class="ms-4">
                            <div class="text-gray-900 dark:text-white font-medium">{{ $team->owner->name }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                {{ $team->owner->email }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Name -->
                <div>
                    <label for="name" class="inline-flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        {{ __('Class Name') }}
                    </label>

                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input id="name"
                            type="text"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition duration-75 dark:text-white"
                            wire:model="state.name"
                            :disabled="! Gate::check('update', $team)" />
                    </div>

                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">
                        @error('name') {{ $message }} @enderror
                    </div>
                </div>

                <!-- Actions -->
                @if (Gate::check('update', $team))
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div x-data="{ shown: false }"
                             x-init="@this.on('saved', () => { shown = true; setTimeout(() => shown = false, 2000); })"
                             x-show="shown"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="flex items-center text-sm text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Saved.') }}
                        </div>

                        <button type="submit"
                                class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 dark:focus:bg-primary-400 dark:focus:ring-offset-primary-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Save') }}
                        </button>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
