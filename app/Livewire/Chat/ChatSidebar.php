<?php

declare(strict_types=1);

namespace App\Livewire\Chat;

use App\Events\MessageSent; // Added if needed for notifications later
use App\Models\Channel;
use App\Models\ChannelCategory;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ChannelNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\Rule;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // Keep Eloquent Collection alias if needed elsewhere
use Illuminate\Support\Collection; // Import the base Collection

class ChatSidebar extends Component
{
    public ?Team $team;
    public ?string $activeChannelId = null;
    // public bool $showMembers = false; // REMOVED - Replaced by viewMode
    public ?string $selectedChannelId = null;
    public ?string $channelId = null; // From filament page?

    public string $viewMode = "channels"; // Consolidated: 'channels', 'directMessages', 'members'
    public \Illuminate\Support\Collection $directMessageChannels; // Consolidated: Holds the collection of DM Channel models

    #[Rule("required|min:2|max:30")]
    public ?string $channelName = null;

    #[Rule("required|min:2|max:30")]
    public ?string $categoryName = null;

    #[Rule("required|min:2|max:100")]
    public ?string $channelDescription = null;

    public ?string $selectedCategoryId = null;
    public string $channelType = "text";
    public bool $isPrivateChannel = false;

    // Operation status variables
    public bool $isDeleting = false;
    public bool $isRenaming = false;
    public bool $isCreatingChannel = false;
    public bool $isCreatingCategory = false;

    // Action states (kept as is, might not be fully used now)
    public array $actionStates = [
        "channel" => [
            "creating" => false,
            "renaming" => false,
            "deleting" => false,
        ],
        "category" => [
            "creating" => false,
        ],
    ];

    public function mount($channelId = null): void
    {
        $this->team = Auth::user()?->currentTeam;
        $this->viewMode = "channels"; // Default view
        $this->directMessageChannels = new Collection(); // Initialize

        // Prioritize channelId passed directly (e.g., from URL)
        if ($channelId) {
            $this->activeChannelId = $channelId; // Set as active
            $initialChannel = Channel::find($channelId);
            if ($initialChannel && $initialChannel->is_dm) {
                $this->viewMode = "directMessages"; // Set view mode if it's a DM
            }
        } else {
            // Fallback to session if no channelId is provided
            $sessionChannelId = session("chat.selected_channel_id");
            if ($sessionChannelId) {
                $this->activeChannelId = $sessionChannelId;
                $initialChannel = Channel::find($sessionChannelId);
                if ($initialChannel && $initialChannel->is_dm) {
                    $this->viewMode = "directMessages";
                }
            }
        }

        // selectedChannelId might still be useful for other logic if needed,
        // but activeChannelId determines the highlighted state.
        $this->selectedChannelId = $this->activeChannelId;
    }

    /**
     * Sets the active channel ID, typically called from ChatWindow after loading.
     */
    #[On("setActiveChannel")]
    public function setActiveChannel(string $channelId): void
    {
        $this->activeChannelId = $channelId;
        // Also update view mode if the active channel type changes
        $channel = Channel::find($channelId);
        if ($channel) {
            $newMode = $channel->is_dm ? "directMessages" : "channels";
            if ($this->viewMode !== $newMode) {
                $this->setViewMode($newMode);
            }
        }
    }

    public function selectChannel(string $channelId): void
    {
        $this->activeChannelId = $channelId;
        $this->dispatch("channelSelected", $channelId);

        // Update view mode based on selected channel type
        $channel = Channel::find($channelId);
        if ($channel) {
            $this->setViewMode($channel->is_dm ? "directMessages" : "channels");
        }
    }

    /**
     * Set the current view mode for the sidebar.
     */
    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ["channels", "directMessages", "members"])) {
            $this->viewMode = $mode;
        }
    }

    /**
     * Toggles the members list view on/off.
     */
    public function toggleMembersList(): void
    {
        $this->setViewMode(
            $this->viewMode === "members" ? "channels" : "members"
        );
    }

    /**
     * Start or select a direct message conversation with a user.
     */
    public function startDirectMessage(string $userId): void
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$this->team) {
            return;
        }

        // Don't start DM with self
        if ($currentUser->id === $userId) {
            Notification::make()
                ->title("Cannot Start DM")
                ->body("You cannot start a direct message with yourself.")
                ->warning()
                ->send();
            return;
        }

        $otherUser = User::find($userId);
        // Ensure the other user exists and belongs to the current team
        if (!$otherUser || !$this->team->hasUser($otherUser)) {
            Notification::make()
                ->title("User Not Found")
                ->body("The selected user is not part of this team.")
                ->warning()
                ->send();
            return;
        }
        $currentUserRole = $currentUser->teamRole($this->team); // Assumes User->teamRole() exists
        $otherUserRole = $otherUser->teamRole($this->team);

        if ($currentUserRole === "parent" && $otherUserRole === "student") {
            Notification::make()
                ->title("Permission Denied")
                ->body("Parents cannot initiate direct messages with students.")
                ->warning()
                ->send();
            return; // Stop the process
        }
        // Find or create the DM channel
        $channel = Channel::findOrCreateDirectMessage($currentUser, $otherUser);

        // Switch to the DM channel
        $this->selectChannel($channel->id);
        // selectChannel already calls setViewMode, no need to call it again here.
    }

    // Methods for channel/category CRUD (startDeleteChannel, deleteChannel, startRenameChannel, etc.)
    // remain largely the same as before.
    // ... [Existing CRUD methods - Assuming they are correct] ...
    public function startDeleteChannel(string $channelId): void
    {
        $this->reset("isDeleting");
        $this->selectedChannelId = $channelId;

        $channel = Channel::find($channelId);
        if (!$channel || $channel->is_dm) {
            // Prevent deleting DM channels this way
            return;
        }

        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title("Permission Denied")
                ->body("You do not have permission to delete this channel.")
                ->danger()
                ->send();
            return;
        }
        $this->isDeleting = true;
        $this->dispatch("channel-delete-initiated", $channelId);
    }
    public function deleteChannel(string $channelId): void
    {
        $this->reset("isDeleting");
        $channel = Channel::find($channelId);
        if (!$channel || $channel->is_dm) {
            return;
        } // Prevent deleting DMs

        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title("Permission Denied")
                ->body("You do not have permission to delete this channel.")
                ->danger()
                ->send();
            return;
        }
        if ($this->activeChannelId === $channelId) {
            $this->activeChannelId = null;
            $this->dispatch("channelSelected", null);
        }
        $channelName = $channel->name;
        $channel->delete(); // Soft delete
        $this->selectedChannelId = null;
        // Notify team members (optional)
        Notification::make()
            ->title("Channel Deleted")
            ->body("The channel '{$channelName}' has been deleted.")
            ->success()
            ->send();
        $this->dispatch("channel-deletion-complete");
    }
    public function startRenameChannel(string $channelId): void
    {
        $this->reset("isRenaming");
        $this->selectedChannelId = $channelId;
        $channel = Channel::find($channelId);
        if (!$channel || $channel->is_dm) {
            return;
        } // Prevent renaming DMs

        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title("Permission Denied")
                ->body("You do not have permission to rename this channel.")
                ->danger()
                ->send();
            return;
        }
        $this->channelName = $channel->name;
        $this->isRenaming = true;
        $this->dispatch("channel-rename-initiated", $channelId);
    }
    public function renameChannel(string $channelId): void
    {
        $this->validate(["channelName" => "required|min:2|max:30"]);
        $this->reset("isRenaming");
        $channel = Channel::find($channelId);
        if (!$channel || $channel->is_dm) {
            return;
        } // Prevent renaming DMs

        if (!$channel->canManage(Auth::user())) {
            Notification::make()
                ->title("Permission Denied")
                ->body("You do not have permission to rename this channel.")
                ->danger()
                ->send();
            return;
        }
        if (
            !Channel::validateUniqueName(
                $this->channelName,
                $channel->team_id,
                $channel->category_id,
                $channel->id
            )
        ) {
            Notification::make()
                ->title("Validation Error")
                ->body(
                    "A channel with this name already exists in this category."
                )
                ->danger()
                ->send();
            return;
        }
        $oldName = $channel->name;
        $channel->name = $this->channelName;
        $channel->slug = Str::slug($this->channelName);
        $channel->save();
        $this->selectedChannelId = null;
        $this->channelName = null;
        Notification::make()
            ->title("Channel Renamed")
            ->body("Channel renamed from '{$oldName}' to '{$channel->name}''.")
            ->success()
            ->send();
        $this->dispatch("channel-rename-complete");
    }
    public function cancelRename(): void
    {
        $this->reset(["isRenaming", "channelName"]);
        $this->dispatch("channel-rename-cancelled");
    }
    public function startCreateChannel(?string $categoryId = null): void
    {
        if (!$this->team || $this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title("Permission Denied")
                ->body("Only team owners can create new channels.")
                ->danger()
                ->send();
            return;
        }
        $this->selectedCategoryId = $categoryId;
        $this->channelName = null;
        $this->channelDescription = null;
        $this->channelType = "text";
        $this->isPrivateChannel = false;
        $this->isCreatingChannel = true;
        $this->dispatch("channel-create-initiated", $categoryId);
    }
    public function createChannel(): void
    {
        $this->validate([
            "channelName" => "required|min:2|max:30",
            "channelDescription" => "required|min:2|max:100",
            "selectedCategoryId" => "required|exists:channel_categories,id",
        ]);
        if (!$this->team || $this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title("Permission Denied")
                ->body("Only team owners can create new channels.")
                ->danger()
                ->send();
            $this->reset("isCreatingChannel");
            return;
        }
        if (
            !Channel::validateUniqueName(
                $this->channelName,
                $this->team->id,
                $this->selectedCategoryId
            )
        ) {
            Notification::make()
                ->title("Validation Error")
                ->body(
                    "A channel with this name already exists in this category."
                )
                ->danger()
                ->send();
            return;
        }
        $this->dispatch("channel-creating");
        $channel = Channel::create([
            "team_id" => $this->team->id,
            "category_id" => $this->selectedCategoryId,
            "name" => $this->channelName,
            "slug" => Str::slug($this->channelName),
            "description" => $this->channelDescription,
            "type" => $this->channelType,
            "is_private" => $this->isPrivateChannel,
            "is_dm" => false, // Explicitly false
        ]);
        // Add members (simplified: add all team members for public, only owner for private initially?)
        $teamMembers = $this->team->users;
        foreach ($teamMembers as $member) {
            $memberPermissions =
                $member->id === $this->team->user_id
                    ? "read,write,manage"
                    : "read,write";
            if (!$channel->is_private || $member->id === $this->team->user_id) {
                // Add all if public, else only owner
                $channel->members()->attach($member->id, [
                    "permissions" => $memberPermissions,
                ]);
            }
        }

        $this->reset([
            "isCreatingChannel",
            "channelName",
            "channelDescription",
            "selectedCategoryId",
        ]);
        Notification::make()
            ->title("Channel Created")
            ->body(
                "The channel '{$channel->name}' has been created successfully."
            )
            ->success()
            ->send();
        $this->selectChannel($channel->id);
        $this->dispatch("channel-creation-complete", $channel->id);
    }
    public function cancelCreateChannel(): void
    {
        $this->reset([
            "isCreatingChannel",
            "channelName",
            "channelDescription",
            "selectedCategoryId",
        ]);
        $this->dispatch("channel-creation-cancelled");
    }
    public function startCreateCategory(): void
    {
        if (!$this->team || $this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title("Permission Denied")
                ->body("Only team owners can create new categories.")
                ->danger()
                ->send();
            return;
        }
        $this->categoryName = null;
        $this->isCreatingCategory = true;
        $this->dispatch("category-create-initiated");
    }
    public function createCategory(): void
    {
        $this->validate(["categoryName" => "required|min:2|max:30"]);
        if (!$this->team || $this->team->user_id !== Auth::id()) {
            Notification::make()
                ->title("Permission Denied")
                ->body("Only team owners can create new categories.")
                ->danger()
                ->send();
            $this->reset("isCreatingCategory");
            return;
        }
        if (
            !ChannelCategory::validateUniqueName(
                $this->categoryName,
                $this->team->id
            )
        ) {
            Notification::make()
                ->title("Validation Error")
                ->body("A category with this name already exists.")
                ->danger()
                ->send();
            return;
        }
        $this->dispatch("category-creating");
        $category = ChannelCategory::create([
            "team_id" => $this->team->id,
            "name" => $this->categoryName,
        ]);
        $this->reset(["isCreatingCategory", "categoryName"]);
        Notification::make()
            ->title("Category Created")
            ->body("The category '{$category->name}' has been created.")
            ->success()
            ->send();
        $this->dispatch("category-creation-complete", $category->id);
        // Optional: $this->startCreateChannel($category->id);
    }
    public function cancelCreateCategory(): void
    {
        $this->reset(["isCreatingCategory", "categoryName"]);
        $this->dispatch("category-creation-cancelled");
    }

    public function render(): View
    {
        $categories = collect();
        $teamMembers = collect();
        $currentUser = Auth::user();
        $activeChannel = null;
        $activeDmOtherUserId = null;
        $userRole = null; // Initialize user role
        if ($this->team && $currentUser) {
            $userRole = $currentUser->teamRole($this->team);
            // Fetch All Team Members using Jetstream's method
            $allTeamMembers = $this->team->allUsers();
            // Includes the owner and all members
            if ($userRole === "parent") {
                // Parents should only see teachers (and maybe other parents? TBD)
                // For now, let's filter out students. Adjust if needed.
                $teamMembers = $allTeamMembers->filter(function ($member) use (
                    $currentUser
                ) {
                    // Keep the current user (self) and non-students
                    // You might need a more robust way to identify teachers if 'teacher' isn't a formal role
                    return $member->id === $currentUser->id ||
                        $member->teamRole($this->team) !== "student";
                });
            } else {
                // Other roles see everyone
                $teamMembers = $allTeamMembers;
            }
            if ($userRole !== "parent") {
                $categories = ChannelCategory::where("team_id", $this->team->id)
                    ->with([
                        "channels" => function ($query) use ($currentUser) {
                            // Pass currentUser for potential access check
                            $query
                                ->where("is_dm", false)
                                // Optionally add ->whereHas('members', fn($q) => $q->where('user_id', $currentUser->id))
                                // if even non-parents should only see channels they are members of
                                ->orderBy("position")
                                ->orderBy("name");
                        },
                    ])
                    ->orderBy("position")
                    ->get();

                // Further filter categories/channels based on canAccess if needed,
                // though canAccess in ChatWindow prevents loading messages anyway.
            }
            // Fetch Categories and their NON-DM Channels
            $categories = ChannelCategory::where("team_id", $this->team->id)
                ->with([
                    "channels" => function ($query) {
                        $query
                            ->where("is_dm", false)
                            ->orderBy("position")
                            ->orderBy("name");
                    },
                ])
                ->orderBy("position")
                ->get();

            // Fetch the active channel model to check its type and members for highlighting
            if ($this->activeChannelId) {
                $activeChannel = Channel::with("members:id")->find(
                    $this->activeChannelId
                );
                // Ensure the channel exists and the current user can access it
                if ($activeChannel && $activeChannel->canAccess($currentUser)) {
                    // <-- CORRECTED CALL
                    if ($activeChannel->is_dm) {
                        $otherMember = $activeChannel->members->firstWhere(
                            "id",
                            "!=",
                            $currentUser->id
                        );
                        if ($otherMember) {
                            $activeDmOtherUserId = $otherMember->id;
                        }
                    }
                } else {
                    // The active channel ID is invalid or inaccessible, clear it
                    $this->activeChannelId = null;
                    $activeDmOtherUserId = null; // Also clear this if channel is inaccessible
                    session()->forget("chat.selected_channel_id"); // Clear session too
                    // Optionally load default or reset. Resetting activeChannelId might trigger default loading if handled elsewhere.
                }
            }
        }

        return view("livewire.chat.chat-sidebar", [
            "teamMembers" => $teamMembers, // Pass potentially filtered list
            "categories" => $categories, // Pass potentially empty list for parents
            "channelTypes" => Channel::TYPES,
            "activeChannelId" => $this->activeChannelId,
            "activeDmOtherUserId" => $activeDmOtherUserId,
            "currentUserRole" => $userRole, // Pass role to view if needed for other UI tweaks
        ]);
    }
}
