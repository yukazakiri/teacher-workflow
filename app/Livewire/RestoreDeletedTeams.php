<?php

namespace App\Livewire;

use App\Models\Team;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RestoreDeletedTeams extends Component
{
    public $deletedTeams = [];
    
    public function mount()
    {
        $this->loadDeletedTeams();
    }
    
    public function loadDeletedTeams()
    {
        // Get all soft-deleted teams owned by the current user
        $this->deletedTeams = Team::onlyTrashed()
            ->where('user_id', Auth::id())
            ->get()
            ->toArray();
    }
    
    public function restoreTeam($teamId)
    {
        $team = Team::onlyTrashed()->find($teamId);
        
        if ($team && $team->user_id === Auth::id()) {
            $team->restore();
            $this->loadDeletedTeams();
            
            // Use Filament notification
            Notification::make()
                ->success()
                ->title('Class Restored')
                ->body('The class has been successfully restored.')
                ->send();
        }
    }
    
    public function render()
    {
        return view('livewire.restore-deleted-teams');
    }
}