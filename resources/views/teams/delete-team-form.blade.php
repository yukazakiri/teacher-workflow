<x-action-section>
    <x-slot name="title">
        {{ __('Delete Class') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Permanently delete this class and all associated data.') }}
    </x-slot>

    <x-slot name="content">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4 mb-5">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-400">{{ __('Warning') }}</h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>{{ __('Once a class is deleted, all of its resources and data will be permanently deleted. This includes:') }}</p>
                        <ul class="list-disc ml-5 mt-2 space-y-1">
                            <li>{{ __('All student enrollments') }}</li>
                            <li>{{ __('All assignments and grades') }}</li>
                            <li>{{ __('All course materials and resources') }}</li>
                            <li>{{ __('All class discussions and announcements') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ __('Please confirm deletion') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                {{ __('Before proceeding, please download any data or information regarding this class that you wish to retain.') }}
            </p>

            <div class="mt-4">
                <label for="confirmDelete" class="inline-flex items-center">
                    <input id="confirmDelete" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" wire:model="confirmDeletion">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('I understand that this action cannot be undone.') }}
                    </span>
                </label>
            </div>
        </div>

        <div class="mt-5 flex items-center">
            <x-danger-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled" class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ __('Delete Class') }}
            </x-danger-button>

            <span class="ml-3 text-sm text-gray-500 dark:text-gray-400 italic">
                {{ __('This action will affect all class members') }}
            </span>
        </div>

        <!-- Delete Class Confirmation Modal -->
        <x-confirmation-modal wire:model.live="confirmingTeamDeletion">
            <x-slot name="title">
                <div class="flex items-center">
                    <svg class="h-6 w-6 text-red-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    {{ __('Delete Class') }}
                </div>
            </x-slot>

            <x-slot name="content">
                <p class="mb-3">
                    {{ __('Are you sure you want to delete this class? Once a class is deleted, all of its resources and data will be permanently deleted.') }}
                </p>

                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-3 text-sm text-red-800 dark:text-red-300">
                    {{ __('This action cannot be undone. All students will lose access to this class and all associated materials.') }}
                </div>

                <div class="mt-4">
                    <label for="confirmDeleteModal" class="flex items-center">
                        <input id="confirmDeleteModal" type="checkbox" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 dark:focus:ring-red-600 dark:focus:ring-offset-gray-800" wire:model="confirmModalDeletion">
                        <span class="ml-2 text-sm font-medium">
                            {{ __('I confirm that I want to permanently delete this class') }}
                        </span>
                    </label>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ml-3" wire:click="deleteTeam" wire:loading.attr="disabled" disabled class="opacity-50">
                    {{ __('Delete Class') }}
                </x-danger-button>
            </x-slot>
        </x-confirmation-modal>
    </x-slot>
</x-action-section>
