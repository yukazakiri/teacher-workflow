<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamAttendance extends Component
{
    public function render()
    {
        $team = Auth::user()->currentTeam;
        
        return view('livewire.team-attendance', [
            'team' => $team,
        ]);
    }
}
