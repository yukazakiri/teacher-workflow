<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Actions\ValidateTeamDeletion;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use App\Models\Team;
use App\Models\User;

class DeleteTeamCustomForm extends Component
{
    use RedirectsActions;

    /**
     * The team instance.
     *
     * @var mixed
     */
    public $team;

    /**
     * Indicates if team deletion is being confirmed.
     *
     * @var bool
     */
    public $confirmingTeamDeletion = false;

    /**
     * Mount the component.
     *
     * @param  mixed  $team
     * @return void
     */
    public function mount($team)
    {
        $this->team = $team;
    }

    /**
     * Delete the team.
     *
     * @param  \Laravel\Jetstream\Actions\ValidateTeamDeletion  $validator
     * @param  \Laravel\Jetstream\Contracts\DeletesTeams  $deleter
     * @return mixed
     */
    public function deleteTeam(ValidateTeamDeletion $validator, DeletesTeams $deleter)
    {
        $user = Auth::user();
        
        // Get all of the user's teams
        $allTeams = $user->ownedTeams->merge($user->teams);
        
        // Check if user has more than one team
        if ($allTeams->count() <= 1) {
            session()->flash('flash.banner', 'You cannot delete your only team!');
            session()->flash('flash.bannerStyle', 'danger');
            $this->confirmingTeamDeletion = false;
            return null;
        }
        
        $validator->validate($user, $this->team);
        
        // Store the current team ID before deletion
        $currentTeamId = $this->team->id;
        
        // Get another team before deleting the current one
        $newTeam = $allTeams->where('id', '!=', $currentTeamId)->first();
        
        $deleter->delete($this->team);
        
        if ($newTeam) {
            // Switch to the new team - update database directly
            DB::table('users')
                ->where('id', $user->id)
                ->update(['current_team_id' => $newTeam->id]);
                
            // Refresh the user model
            $user = User::find($user->id);
        }
        
        $this->team = null;
        
        session()->flash('flash.banner', 'Team deleted successfully!');
        session()->flash('flash.bannerStyle', 'success');
        
        return redirect()->route('filament.admin.pages.dashboard');
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $user = Auth::user();
        $allTeams = $user->ownedTeams->merge($user->teams);
        $canDelete = $allTeams->count() > 1 && !$this->team->personal_team;
        
        return view('teams.delete-team-form', [
            'canDelete' => $canDelete
        ]);
    }
}