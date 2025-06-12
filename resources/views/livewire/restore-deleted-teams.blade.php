<div>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Restore Deleted Classes') }}
        </x-slot>

        <x-slot name="description">
            {{ __('View and restore classes that you have previously deleted.') }}
        </x-slot>

        @if(count($deletedTeams) > 0)
            <div class="space-y-4">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Class Name') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Deleted On') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($deletedTeams as $team)
                                <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $team['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($team['deleted_at'])->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <x-filament::button
                                            color="success"
                                            size="sm"
                                            wire:click="restoreTeam('{{ $team['id'] }}')"
                                            wire:loading.attr="disabled"
                                            icon="heroicon-o-arrow-path"
                                        >
                                            {{ __('Restore') }}
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="py-6 flex justify-center items-center bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                <div class="text-center">
                    <x-filament::icon 
                        icon="heroicon-o-trash" 
                        class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                    />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No Deleted Classes') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('You have no deleted classes to restore.') }}
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>
</div>