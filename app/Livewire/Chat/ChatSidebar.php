<?php

declare(strict_types=1);

namespace App\Livewire\Chat;

use App\Models\Channel;
use App\Models\Team;
use App\Notifications\ChannelNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\Rule;
use App\Models\Category;
use App\Models\ChannelCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ChatSidebar extends Component
{
    public ?Team $team;
    public ?string $activeChannelId = null;
    public bool $showMembers = false;
    public ?string $selectedChannelId = null;

    #[Rule('required|min:2|max:30')]
    public ?string $channelName = null;

    #[Rule('required|min:2|max:30')]
    public ?string $categoryName = null;

    #[Rule('required|min:2|max:100')]
    public ?string $channelDescription = null;

    public ?string $selectedCategoryId = null;

    public string $channelType = 'text';

    public bool $isPrivateChannel = false;

    // Operation status variables
    public bool $isDeleting = false;
    public bool $isRenaming = false;
    public bool $isCreatingChannel = false;
    public bool $isCreatingCategory = false;

    // Action states
    public array $actionStates = [
        'channel' => [
            'creating' => false,
            'renaming' => false,
            'deleting' => false
        ],
        'category' => [
            'creating' => false
        ]
    ];

    public function mount(): void
    {
        $this->team = Auth::user()?->currentTeam;

        // Attempt to set the initial active channel ID based on localStorage or default
        $this->dispatch('requestInitialChannelId');
    }

    /**
     * Sets the active channel ID, typically called from ChatWindow after loading.
     */
    #[On('setActiveChannel')]
    public function setActiveChannel(string $channelId): void
    {
        $this->activeChannelId = $channelId;
    }

    public function selectChannel(string $channelId): void
    {
        // Set immediately for visual feedback, ChatWindow will confirm
        $this->activeChannelId = $channelId;

        // Tell ChatWindow to load this channel
        $this->dispatch('channelSelected', $channelId);
    }

    /**
     * Alpine.js enabled context menu toggle
     */
    public function openContextMenu(string $channelId): void
    {
        $this->selectedChannelId = $channelId;
    }

    public function toggleMembersList(): void
    {
        $this->showMembers = !$this->showMembers;
    }

    // ======== Delete Channel Operations ========

    /**
     * Start delete operation - checks permissions
     */
    public function startDeleteChannel(string $channelId): void
    {
        $this->reset('isDeleting');
        $this->selectedChannelId = $channelId;

        $channel = Channel::find($channelId);
        if (!$channel) {
            return;
        }

        // Check if user has permission to delete channel
        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to delete this channel.')
                ->danger()
                ->send();
            return;
        }

        $this->isDeleting = true;
        $this->dispatch('channel-delete-initiated', $channelId);
    }

    /**
     * Confirm and execute channel deletion
     */
    public function deleteChannel(string $channelId): void
    {
        $this->reset('isDeleting');

        $channel = Channel::find($channelId);
        if (!$channel) {
            return;
        }

        // Check if user has permission to delete channel
        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to delete this channel.')
                ->danger()
                ->send();
            return;
        }

        // If this is the active channel, set active to null first
        if ($this->activeChannelId === $channelId) {
            $this->activeChannelId = null;
            $this->dispatch('channelSelected', null);
        }

        // Store channel info before deletion for notification
        $channelName = $channel->name;

        // Delete the channel (soft delete)
        $channel->delete();

        $this->selectedChannelId = null;

        // Notify team members about channel deletion
        $teamUsers = $this->team->users()->where('id', '!=', Auth::id())->get();
        foreach ($teamUsers as $user) {
            $user->notify(new ChannelNotification('deleted', $channel, Auth::user()));
        }

        Notification::make()
            ->title('Channel Deleted')
            ->body("The channel '{$channelName}' has been deleted successfully.")
            ->success()
            ->send();

        $this->dispatch('channel-deletion-complete');
    }

    // ======== Rename Channel Operations ========

    /**
     * Start rename operation - checks permissions
     */
    public function startRenameChannel(string $channelId): void
    {
        $this->reset('isRenaming');
        $this->selectedChannelId = $channelId;

        $channel = Channel::find($channelId);
        if (!$channel) {
            return;
        }

        // Check if user has permission to rename channel
        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to rename this channel.')
                ->danger()
                ->send();
            return;
        }

        $this->channelName = $channel->name;
        $this->isRenaming = true;
        $this->dispatch('channel-rename-initiated', $channelId);
    }

    /**
     * Execute channel rename with validation
     */
    public function renameChannel(string $channelId): void
    {
        $this->validate([
            'channelName' => 'required|min:2|max:30'
        ]);

        $this->reset('isRenaming');

        $channel = Channel::find($channelId);
        if (!$channel) {
            return;
        }

        // Check if user has permission to rename channel
        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to rename this channel.')
                ->danger()
                ->send();
            return;
        }

        // Validate channel name uniqueness within the category
        if (!Channel::validateUniqueName($this->channelName, $channel->team_id, $channel->category_id, $channel->id)) {
            Notification::make()
                ->title('Validation Error')
                ->body('A channel with this name already exists in this category.')
                ->danger()
                ->send();
            return;
        }

        // Keep old name for notification
        $oldName = $channel->name;

        // Update channel
        $channel->name = $this->channelName;
        $channel->slug = Str::slug($this->channelName);
        $channel->save();

        // Reset state
        $this->selectedChannelId = null;
        $this->channelName = null;



        Notification::make()
            ->title('Channel Renamed')
            ->body("Channel renamed from '{$oldName}' to '{$channel->name}'.")
            ->success()
            ->send();

        $this->dispatch('channel-rename-complete');
    }

    public function cancelRename(): void
    {
        $this->reset(['isRenaming', 'channelName']);
        $this->dispatch('channel-rename-cancelled');
    }

    // ======== Create Channel Operations ========

    /**
     * Start the channel creation process
     */
    public function startCreateChannel(?string $categoryId = null): void
    {
        if (!$this->team) {
            return;
        }

        // Check if user has permission to create channel
        if ($this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title('Permission Denied')
                ->body('Only team owners can create new channels.')
                ->danger()
                ->send();
            return;
        }

        $this->selectedCategoryId = $categoryId;
        $this->channelName = null;
        $this->channelDescription = null;
        $this->channelType = 'text';
        $this->isPrivateChannel = false;
        $this->isCreatingChannel = true;

        $this->dispatch('channel-create-initiated', $categoryId);
    }

    /**
     * Execute channel creation with validation
     */
    public function createChannel(): void
    {
        $this->validate([
            'channelName' => 'required|min:2|max:30',
            'channelDescription' => 'required|min:2|max:100',
            'selectedCategoryId' => 'required|exists:channel_categories,id'
        ]);

        if (!$this->team) {
            $this->reset('isCreatingChannel');
            return;
        }

        // Check if user has permission to create channel
        if ($this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title('Permission Denied')
                ->body('Only team owners can create new channels.')
                ->danger()
                ->send();

            $this->reset('isCreatingChannel');
            return;
        }

        // Validate channel name uniqueness within the category
        if (!Channel::validateUniqueName($this->channelName, $this->team->id, $this->selectedCategoryId)) {
            Notification::make()
                ->title('Validation Error')
                ->body('A channel with this name already exists in this category.')
                ->danger()
                ->send();
            return;
        }

        // Create the new channel with loading state
        $this->dispatch('channel-creating');

        $channel = Channel::create([
            'team_id' => $this->team->id,
            'category_id' => $this->selectedCategoryId,
            'name' => $this->channelName,
            'slug' => Str::slug($this->channelName),
            'description' => $this->channelDescription,
            'type' => $this->channelType,
            'is_private' => $this->isPrivateChannel,
        ]);

        // Add all team members to the channel with appropriate permissions
        $teamMembers = $this->team->users;
        foreach ($teamMembers as $member) {
            // Default permissions for all members
            $memberPermissions = 'read,write';

            // Extended permissions for team owner
            if ($member->id === $this->team->user_id) {
                $memberPermissions = 'read,write,manage';
            }

            $channel->members()->attach($member->id, ['permissions' => $memberPermissions]);
        }

        // Reset state
        $this->reset(['isCreatingChannel', 'channelName', 'channelDescription', 'selectedCategoryId']);

        // Notify team members about new channel


        Notification::make()
            ->title('Channel Created')
            ->body("The channel '{$channel->name}' has been created successfully.")
            ->success()
            ->send();

        // Select the new channel
        $this->selectChannel($channel->id);
        $this->dispatch('channel-creation-complete', $channel->id);
    }

    public function cancelCreateChannel(): void
    {
        $this->reset(['isCreatingChannel', 'channelName', 'channelDescription', 'selectedCategoryId']);
        $this->dispatch('channel-creation-cancelled');
    }

    // ======== Create Category Operations ========

    /**
     * Start the category creation process
     */
    public function startCreateCategory(): void
    {
        if (!$this->team) {
            return;
        }

        // Check if user has permission to create category
        if ($this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title('Permission Denied')
                ->body('Only team owners can create new categories.')
                ->danger()
                ->send();
            return;
        }

        $this->categoryName = null;
        $this->isCreatingCategory = true;
        $this->dispatch('category-create-initiated');
    }

    /**
     * Execute category creation with validation
     */
    public function createCategory(): void
    {
        $this->validate([
            'categoryName' => 'required|min:2|max:30'
        ]);

        if (!$this->team) {
            $this->reset('isCreatingCategory');
            return;
        }

        // Check if user has permission to create category
        if ($this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title('Permission Denied')
                ->body('Only team owners can create new categories.')
                ->danger()
                ->send();

            $this->reset('isCreatingCategory');
            return;
        }

        // Validate category name uniqueness within the team
        if (!ChannelCategory::validateUniqueName($this->categoryName, $this->team->id)) {
            Notification::make()
                ->title('Validation Error')
                ->body('A category with this name already exists.')
                ->danger()
                ->send();
            return;
        }

        // Create the new category with loading state
        $this->dispatch('category-creating');

        $category = ChannelCategory::create([
            'team_id' => $this->team->id,
            'name' => $this->categoryName,
        ]);

        // Reset state
        $this->reset(['isCreatingCategory', 'categoryName']);

        Notification::make()
            ->title('Category Created')
            ->body("The category '{$category->name}' has been created successfully.")
            ->success()
            ->send();

        $this->dispatch('category-creation-complete', $category->id);

        // Prompt to create a channel in this category
        $this->startCreateChannel($category->id);
    }

    public function cancelCreateCategory(): void
    {
        $this->reset(['isCreatingCategory', 'categoryName']);
        $this->dispatch('category-creation-cancelled');
    }

    public function render(): View
    {
        $categories = collect();
        $teamMembers = collect();

        if ($this->team) {
            // Get categories with channels
            $categories = ChannelCategory::where('team_id', $this->team->id)
                ->with(['channels' => function ($query) {
                    $query->orderBy('position')->orderBy('name');
                }])
                ->orderBy('position')
                ->get();

            // Get team members if needed
            if ($this->showMembers) {
                $teamMembers = $this->team->users;
            }
        }

        return view('livewire.chat.chat-sidebar', [
            'categories' => $categories,
            'teamMembers' => $teamMembers,
            'channelTypes' => Channel::TYPES,
        ]);
    }
}
