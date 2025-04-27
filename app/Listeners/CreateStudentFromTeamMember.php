<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Jetstream\Events\TeamMemberAdded as JetstreamTeamMemberAdded;

class CreateStudentFromTeamMember implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(JetstreamTeamMemberAdded $event): void
    {
        $team = $event->team;
        $user = $event->user;

        // Check if this user already has a student record in this team
        $existingStudent = Student::where('team_id', $team->id)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if ($existingStudent) {
            // If a student record exists with this email but no user_id, link it
            if ($existingStudent->user_id === null) {
                $existingStudent->update(['user_id' => $user->id]);
            }

            return;
        }

        // Create a new student record
        Student::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
    }
}
