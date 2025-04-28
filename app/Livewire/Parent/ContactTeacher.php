<?php

declare(strict_types=1);

namespace App\Livewire\Parent;

use App\Models\Channel;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Exception; // Import Exception
use Illuminate\Http\RedirectResponse; // Import RedirectResponse

class ContactTeacher extends Component
{
    public User $teacher;

    public function mount(User $teacher): void
    {
        $this->teacher = $teacher;
    }

    /**
     * Start a direct message with the teacher and redirect.
     */
    public function startDirectMessage(): mixed
    {
        // Specify return type
        // Specify return type
        $parent = Auth::user();

        if (!$parent) {
            // This case is unlikely in Filament but good practice
            Notification::make()
                ->title("Authentication Error")
                ->body("You must be logged in to send messages.")
                ->danger()
                ->send();
            return null; // Indicate failure, stay on page
        }

        if (!$this->teacher) {
            Notification::make()
                ->title("Error")
                ->body("Teacher information is missing.")
                ->danger()
                ->send();
            return null; // Stay on the page
        }

        // Ensure the parent has a current team context if DMs are tied to teams
        // The findOrCreateDirectMessage should ideally handle the team context
        if (!$parent->currentTeam) {
            Notification::make()
                ->title("Error")
                ->body("Team context missing. Cannot start direct message.")
                ->danger()
                ->send();
            return null; // Stay on the page
        }

        try {
            // Ensure the teacher is part of the parent's current team context
            // Adjust this check if your team membership logic differs
            if (!$this->teacher->belongsToTeam($parent->currentTeam)) {
                Notification::make()
                    ->title("Error")
                    ->body(
                        "Cannot message teacher outside of your current team context."
                    )
                    ->danger()
                    ->send();
                return null; // Stay on the page
            }

            // Find or create the DM channel using the static method
            // Pass the users and the current team context might be implicit or needed explicitly
            $channel = Channel::findOrCreateDirectMessage(
                $parent,
                $this->teacher
            );

            // Redirect to the messages page with the channel ID
            // Use the correct route name for your Filament messages page
            return redirect()->route("filament.app.pages.messages", [
                "tenant" => $parent->currentTeam->id,
                "channel" => $channel->id,
            ]);
        } catch (Exception $e) {
            report($e); // Log the exception for debugging

            Notification::make()
                ->title("Failed to start conversation")
                ->body("An unexpected error occurred: " . $e->getMessage()) // Provide more detail if appropriate
                ->danger()
                ->send();

            return null; // Stay on the current page after error
        }
    }

    public function render(): View
    {
        return view("livewire.parent.contact-teacher");
    }
}
