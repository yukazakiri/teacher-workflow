<x-action-section>
    <x-slot name="title">
        {{ __('Delete Class') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Permanently delete this class.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once a class is deleted, all of its resources and data will be permanently deleted. Before deleting this class, please download any data or information that you wish to retain.') }}
        </div>

        @if ($canDelete)
            <div class="mt-5">
                <x-danger-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                    {{ __('Delete Class') }}
                </x-danger-button>
            </div>

            <!-- Delete Team Confirmation Modal -->
            <x-confirmation-modal wire:model.live="confirmingTeamDeletion">
                <x-slot name="title">
                    {{ __('Delete Class') }}
                </x-slot>

                <x-slot name="content">
                    {{ __('Are you sure you would like to delete this class? Once a class is deleted, all of its resources and data will be permanently deleted.') }}
                </x-slot>

                <x-slot name="footer">
                    <x-secondary-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ml-3" wire:click="deleteTeam" wire:loading.attr="disabled">
                        {{ __('Delete Class') }}
                    </x-danger-button>
                </x-slot>
            </x-confirmation-modal>
        @else
            <div class="mt-5">
                <p class="text-sm text-red-600 dark:text-red-400">
                    {{ __('You cannot delete your only class. You must create another class before deleting this one.') }}
                </p>
            </div>
        @endif
    </x-slot>
</x-action-section>