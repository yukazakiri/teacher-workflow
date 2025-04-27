<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\StudentHelper;
use Filament\Notifications\Notification;
use Laravel\Jetstream\Jetstream;

class SelectRole extends Component
{
    public string $selectedRole = '';
    
    public function mount()
    {
        // Default to student role if selection is needed
        $this->selectedRole = 'student';
    }
    
    public function setRole()
    {
        // Validate role
        $this->validate([
            'selectedRole' => 'required|in:student,parent',
        ]);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            Notification::make()
                ->danger()
                ->title('No Class Found')
                ->body('Unable to set your role as you are not part of a class.')
                ->send();
            return;
        }
        
        // Update the user's role in the team
        DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->update([
                'role' => $this->selectedRole,
                'updated_at' => now(),
            ]);
        
        // If role is student, ensure a student record exists
        if ($this->selectedRole === 'student') {
            StudentHelper::createStudentRecord($user, $team);
        }
        
        Notification::make()
            ->success()
            ->title('Role Set Successfully')
            ->body('Your role has been set to ' . ucfirst($this->selectedRole))
            ->send();
        
        // Refresh the page to update UI
        $this->redirect(request()->header('Referer'));
    }
    
    public function render()
    {
        $roles = collect(Jetstream::$roles)->filter(function ($role) {
            // Only show student and parent roles
            return in_array($role->key, ['student', 'parent']);
        });
        
        return view('livewire.select-role', [
            'roles' => $roles,
        ]);
    }
}
