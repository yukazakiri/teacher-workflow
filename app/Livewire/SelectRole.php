<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\StudentHelper;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Laravel\Jetstream\Jetstream;

class SelectRole extends Component
{
    public string $selectedRole = "";

    public function mount(): void
    {
        // Default to student role if selection is needed
        $this->selectedRole = "student";
    }

    /**
     * Set the user's role within their current team and redirect.
     *
     * @return RedirectResponse|Redirector|void
     */
    public function setRole()
    {
        // Validate role
        $this->validate([
            "selectedRole" => "required|in:student,parent,teacher", // Added teacher
        ]);

        $user = Auth::user();
        if (!$user) {
            // Handle case where user is not authenticated, though typically middleware prevents this.
            return redirect("/login");
        }

        $team = $user->currentTeam;

        if (!$team) {
            Notification::make()
                ->danger()
                ->title("No Class Found")
                ->body(
                    "Unable to set your role as you are not part of a class."
                )
                ->send();
            return;
        }

        // Update the user's role in the team
        DB::table("team_user")
            ->where("team_id", $team->id)
            ->where("user_id", $user->id)
            ->update([
                "role" => $this->selectedRole,
                "updated_at" => now(),
            ]);

        Notification::make()
            ->success()
            ->title("Role Set Successfully")
            ->body("Your role has been set to " . ucfirst($this->selectedRole))
            ->send();

        // Redirect based on the selected role
        if ($this->selectedRole === "student") {
            StudentHelper::createStudentRecord($user, $team);
            return redirect("/app/$team->id/student-dashboard");
        } elseif ($this->selectedRole === "parent") {
            // Add any teacher-specific setup logic here if needed
            return redirect("/app/$team->id/parent-dashboard");
        }

        // Default redirect if role doesn't match specific cases (optional)
        return redirect("/");
    }

    public function render(): View
    {
        // Note: This filtering might need adjustment if 'teacher' role
        // is not part of Jetstream::$roles or needs to be selectable.
        $roles = collect(Jetstream::$roles)->filter(function ($role) {
            // Only show student and parent roles currently
            // Consider adding 'teacher' here if it should be an option
            return in_array($role->key, ["student", "parent"]);
        });

        return view("livewire.select-role", [
            "roles" => $roles,
        ]);
    }
}
