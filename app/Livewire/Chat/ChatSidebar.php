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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;

class ChatSidebar extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?Team $team;
    public ?string $activeChannelId = null;
    // public bool $showMembers = false; // REMOVED - Replaced by viewMode
    public ?string $selectedChannelId = null;
    public ?string $channelId = null; // From filament page?

    // public \Illuminate\Support\Collection $directMessageChannels; // REMOVED - Not needed for carousel
    public string $searchTerm = ''; // Added for DM search

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
    // REMOVED Operation status variables (isDeleting, isRenaming, etc.)
    // REMOVED actionStates array

    public function mount($channelId = null): void
    {
        $this->team = Auth::user()?->currentTeam;
        $this->selectedChannelId = $channelId;
        // $this->directMessageChannels = new Collection(); // REMOVED

        // Sync activeChannelId with session if no channelId is provided
        if (!$channelId) {
            $sessionChannelId = session("chat.selected_channel_id");
            if ($sessionChannelId) {
                $this->activeChannelId = $sessionChannelId;
            }
        } else {
             $this->activeChannelId = $channelId;
        }

        // $this->loadDirectMessages(); // REMOVED

        // Attempt to set the initial active channel ID based on localStorage or default
        // $this->dispatch('requestInitialChannelId'); // Might interfere, keep commented for now

        // Initialize form data (needed for Filament Forms)
        $this->form->fill();
    }

    /**
     * Sets the active channel ID, typically called from ChatWindow after loading.
     */
    #[On("setActiveChannel")]
    public function setActiveChannel(string $channelId): void
    {
        $this->activeChannelId = $channelId;
        // No viewMode update needed
    }

    public function selectChannel(string $channelId): void
    {
        $this->activeChannelId = $channelId;
        $this->dispatch("channelSelected", $channelId);
        // No viewMode update needed
    }

    // REMOVED loadDirectMessages method


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

        // Find or create the DM channel
        $channel = Channel::findOrCreateDirectMessage($currentUser, $otherUser);

        // Switch to the DM channel
        $this->selectChannel($channel->id);
        // $this->loadDirectMessages(); // REMOVED - No longer needed
    }

    // --- Filament Actions ---

    protected function getCategoryOptions(): array
    {
        if (!$this->team) return [];
        return ChannelCategory::where('team_id', $this->team->id)
            ->orderBy('position')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function createChannelAction(): Action
    {
        return Action::make('createChannelAction')
            ->label('Create Channel')
            ->icon('heroicon-o-plus-circle')
            ->form([
                TextInput::make('channelName')
                    ->label('Channel Name')
                    ->required()
                    ->minLength(2)
                    ->maxLength(30)
                    ->live(onBlur: true) // Optional: Live validation on blur
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Basic unique check within the form (more robust check in action)
                        if ($this->team && $this->selectedCategoryId) {
                             if (!Channel::validateUniqueName($state, $this->team->id, $this->selectedCategoryId)) {
                                 // This validation might be better placed in the action logic
                                 // or using a Rule object for cleaner separation.
                                 // For simplicity here, we just note it.
                             }
                        }
                    }),
                Textarea::make('channelDescription') // Changed to Textarea
                    ->label('Description')
                    ->required() // Make description required as per old validation
                    ->minLength(2)
                    ->maxLength(100)
                    ->rows(3),
                Select::make('selectedCategoryId')
                    ->label('Category')
                    ->options($this->getCategoryOptions())
                    ->required()
                    ->searchable(), // Add search if many categories
                Select::make('channelType')
                    ->label('Channel Type')
                    ->options(Channel::TYPES)
                    ->default('text')
                    ->required(),
                Toggle::make('isPrivateChannel')
                    ->label('Private Channel')
                    ->helperText('Only invited members will be able to see this channel.')
                    ->default(false),
            ])
            ->action(function (array $data) {
                if (!$this->team || $this->team->user_id !== Auth::id()) {
                    Notification::make()->title("Permission Denied")->body("Only team owners can create new channels.")->danger()->send();
                    return;
                }

                // Re-validate uniqueness server-side before creation
                if (!Channel::validateUniqueName($data['channelName'], $this->team->id, $data['selectedCategoryId'])) {
                    Notification::make()->title("Validation Error")->body("A channel with this name already exists in this category.")->danger()->send();
                    return;
                }

                $channel = Channel::create([
                    "team_id" => $this->team->id,
                    "category_id" => $data['selectedCategoryId'],
                    "name" => $data['channelName'],
                    "slug" => Str::slug($data['channelName']),
                    "description" => $data['channelDescription'],
                    "type" => $data['channelType'],
                    "is_private" => $data['isPrivateChannel'],
                    "is_dm" => false,
                ]);

                // Add members
                $teamMembers = $this->team->users;
                foreach ($teamMembers as $member) {
                    $permissionString = $member->id === $this->team->user_id ? "read,write,manage" : "read,write";
                    $permissionsArray = explode(',', $permissionString);
                    if (!$channel->is_private || $member->id === $this->team->user_id) {
                        $channel->members()->attach($member->id, ["permissions" => $permissionsArray]);
                    }
                }

                Notification::make()->title("Channel Created")->body("The channel '{$channel->name}' has been created successfully.")->success()->send();
                $this->selectChannel($channel->id); // Select the new channel
            })
            ->modalWidth('xl') // Adjust modal size if needed
            ->visible(fn (): bool => $this->team && $this->team->user_id === Auth::id()); // Only visible to team owner
    }

     public function createCategoryAction(): Action
     {
         return Action::make('createCategoryAction')
             ->label('Create Category')
             ->icon('heroicon-o-folder-plus')
             ->form([
                 TextInput::make('categoryName')
                     ->label('Category Name')
                     ->required()
                     ->minLength(2)
                     ->maxLength(30)
                     ->live(onBlur: true)
                     ->afterStateUpdated(function ($state, callable $set) {
                         // Basic unique check
                         if ($this->team) {
                              if (!ChannelCategory::validateUniqueName($state, $this->team->id)) {
                                  // Again, better validation in action logic or Rule object
                              }
                         }
                     }),
             ])
             ->action(function (array $data) {
                 if (!$this->team || $this->team->user_id !== Auth::id()) {
                     Notification::make()->title("Permission Denied")->body("Only team owners can create new categories.")->danger()->send();
                     return;
                 }

                 if (!ChannelCategory::validateUniqueName($data['categoryName'], $this->team->id)) {
                     Notification::make()->title("Validation Error")->body("A category with this name already exists.")->danger()->send();
                     return;
                 }

                 $category = ChannelCategory::create([
                     "team_id" => $this->team->id,
                     "name" => $data['categoryName'],
                 ]);

                 Notification::make()->title("Category Created")->body("The category '{$category->name}' has been created.")->success()->send();
                 // Optionally trigger create channel action with this category pre-selected?
                 // $this->mountAction('createChannelAction', ['selectedCategoryId' => $category->id]);
             })
             ->modalWidth('md')
             ->visible(fn (): bool => $this->team && $this->team->user_id === Auth::id()); // Only visible to team owner
     }

     public function deleteChannelAction(): Action
     {
         return Action::make('deleteChannelAction')
             ->label('Delete Channel')
             ->icon('heroicon-o-trash')
             ->color('danger')
             ->requiresConfirmation()
             ->modalHeading('Delete Channel')
             ->modalDescription('Are you sure you want to delete this channel? All messages will be permanently lost. This action cannot be undone.')
             ->modalSubmitActionLabel('Yes, delete it')
             ->action(function (array $arguments) {
                 $channelId = $arguments['channelId'] ?? null;
                 if (!$channelId) return;

                 $channel = Channel::find($channelId);
                 if (!$channel || $channel->is_dm) {
                     Notification::make()->warning()->title('Cannot Delete')->body('Direct messages cannot be deleted.')->send();
                     return;
                 }

                 if (!$channel->canManage(Auth::user())) {
                     Notification::make()->danger()->title('Permission Denied')->body('You cannot delete this channel.')->send();
                     return;
                 }

                 if ($this->activeChannelId === $channelId) {
                     $this->activeChannelId = null;
                     $this->dispatch("channelSelected", null); // Deselect if active
                 }

                 $channelName = $channel->name;
                 $channel->delete(); // Soft delete

                 Notification::make()->success()->title('Channel Deleted')->body("The channel '{$channelName}' has been deleted.")->send();
             })
             // Note: Visibility/permission check happens when mounting the action in the view
             ->modalWidth('lg');
     }

    // --- End Filament Actions ---


    // REMOVED OLD CRUD METHODS (startDeleteChannel, deleteChannel, startRenameChannel, renameChannel, cancelRename, startCreateChannel, createChannel, cancelCreateChannel, startCreateCategory, createCategory, cancelCreateCategory)


    public function render(): View
    {
        $categories = collect();
        $teamMembers = collect(); // Initialize
        $currentUser = Auth::user();
        $activeChannel = null;
        $activeDmOtherUserId = null; // Needed for carousel highlight

        if ($this->team && $currentUser) {
            // Fetch All Team Members
            $allTeamMembers = $this->team->allUsers();

            // Filter members based on search term
            if (!empty($this->searchTerm)) {
                $searchTermLower = strtolower($this->searchTerm);
                $teamMembers = $allTeamMembers->filter(function ($member) use ($searchTermLower) {
                    return str_contains(strtolower($member->name), $searchTermLower);
                });
            } else {
                $teamMembers = $allTeamMembers;
            }


            // Fetch Categories and their NON-DM Channels
            $categories = ChannelCategory::where('team_id', $this->team->id)
                ->with(['channels' => function ($query) {
                    // Eager load non-DM channels
                    $query->where('is_dm', false)->orderBy('position')->orderBy('name');
                }])
                ->orderBy('position')
                ->get();

            // Fetch the active channel model to determine if it's a DM and find the other user ID
            if ($this->activeChannelId) {
                 $activeChannel = Channel::with('members:id')->find($this->activeChannelId); // Eager load members
                 if ($activeChannel && $activeChannel->is_dm) {
                     // Find the ID of the other member in the active DM channel
                     $otherMember = $activeChannel->members->firstWhere('id', '!=', $currentUser->id);
                     if ($otherMember) {
                         $activeDmOtherUserId = $otherMember->id;
                     }
                 }
            }
        }

        return view('livewire.chat.chat-sidebar', [
            'teamMembers' => $teamMembers, // Pass the potentially filtered members
            // 'directMessageChannels' => $this->directMessageChannels, // REMOVED
            'categories' => $categories,
            'channelTypes' => Channel::TYPES, // For create form
            'activeChannelId' => $this->activeChannelId,
            'activeDmOtherUserId' => $activeDmOtherUserId, // Pass the ID for carousel highlighting
        ]);
    }
}
