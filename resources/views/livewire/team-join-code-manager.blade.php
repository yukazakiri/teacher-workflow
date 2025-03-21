<div>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Class Join Code') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Share this code with students to allow them to join your class. Keep it secure to prevent unauthorized access.') }}
        </x-slot>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="w-full md:w-auto">
                <div class="border rounded-lg px-6 py-4 bg-gray-50 dark:bg-gray-800 flex flex-col md:flex-row items-start md:items-center gap-3">
                    @if($team->join_code)
                        <div class="flex items-center">
                            <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">{{ __('Code:') }}</span>
                            <span class="text-2xl font-mono font-bold tracking-wider text-primary-600 dark:text-primary-400">{{ $team->join_code }}</span>
                        </div>

                        <div class="flex items-center md:ml-auto">
                            <button
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30 transition duration-150"
                                x-data="{}"
                                wire:click="copyJoinCode"
                            >
                                <x-heroicon-o-clipboard-document class="w-4 h-4 mr-1" />
                                {{ __('Copy') }}
                            </button>
                        </div>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">{{ __('No join code available') }}</span>
                    @endif
                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Students must use this code with the "Join Existing Class" option to gain access.') }}
                </p>
            </div>

            <div class="w-full md:w-auto">
                <x-filament::button
                    color="warning"
                    wire:click="regenerateJoinCode"
                    wire:loading.attr="disabled"
                    class="w-full md:w-auto"
                >
                    <span wire:loading.remove wire:target="regenerateJoinCode" class="flex items-center gap-1">
                        <x-heroicon-o-arrow-path class="w-5 h-5" />
                        {{ $team->join_code ? __('Regenerate Code') : __('Generate Code') }}
                    </span>
                    <span wire:loading wire:target="regenerateJoinCode" class="flex items-center gap-1">
                        <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin" />
                        {{ __('Processing...') }}
                    </span>
                </x-filament::button>

                @if($team->join_code)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center md:text-left">
                        {{ __('Regenerating will invalidate the current code.') }}
                    </p>
                @endif
            </div>
        </div>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('copy-to-clipboard', ({ text }) => {
                    navigator.clipboard.writeText(text);
                });
            });
        </script>
    </x-filament::section>
</div>
