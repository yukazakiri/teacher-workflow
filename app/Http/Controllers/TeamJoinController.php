<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Helpers\StudentHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamJoinController extends Controller
{
    /**
     * Show the team join confirmation page.
     */
    public function show(string $joinCode): View
    {
        $team = Team::where('join_code', $joinCode)->firstOrFail();
        $user = Auth::user();
        $alreadyMember = $user->belongsToTeam($team);
        
        return view('teams.join', [
            'team' => $team,
            'alreadyMember' => $alreadyMember,
        ]);
    }
    
    /**
     * Process the team join request.
     */
    public function join(Request $request, string $joinCode)
    {
        $team = Team::where('join_code', $joinCode)->firstOrFail();
        $user = Auth::user();
        
        // Check if user is already a member
        if ($user->belongsToTeam($team)) {
            return redirect()->route('teams.show', ['team' => $team->id])
                ->with('status', 'You are already a member of this team.');
        }
        
        // Add user to the team
        $user->teams()->attach($team, ['role' => 'member']);
        
        // Create a student record
        StudentHelper::createStudentRecord($user, $team);
        
        // Switch to the newly joined team
        $user->switchTeam($team);
        
        return redirect()->route('teams.show', ['team' => $team->id])
            ->with('status', 'You have successfully joined the team!');
    }
    
    /**
     * Create a student record for the user in the team
     */
    protected function createStudentRecord(User $user, Team $team): void
    {
        StudentHelper::createStudentRecord($user, $team);
    }
} 