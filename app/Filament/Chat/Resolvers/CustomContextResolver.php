<?php

namespace App\Filament\Chat\Resolvers;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use AssistantEngine\Filament\Chat\Resolvers\ContextResolver;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Jetstream;

class CustomContextResolver extends ContextResolver
{
    public function resolve(Page $page): array
    {
        // Get the base context from the parent resolver
        $result = parent::resolve($page);

        // Add current user context
        $this->addCurrentUserContext($result);

        // Add current team context
        $this->addCurrentTeamContext($result);

        return $result;
    }

    /**
     * Add the current authenticated user to the context
     */
    protected function addCurrentUserContext(array &$result): void
    {
        $user = Auth::user();

        if ($user) {
            $userClass = get_class($user);

            if (isset($result[$userClass])) {
                // If we already have users in the context, make sure the current user is included
                $currentUserExists = false;
                foreach ($result[$userClass] as $existingUser) {
                    if ($existingUser['id'] === $user->id) {
                        $currentUserExists = true;
                        break;
                    }
                }

                if (! $currentUserExists) {
                    $result[$userClass][] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'current_user' => true,
                    ];
                }
            } else {
                // If no users in context yet, add the current user
                $result[$userClass] = [[
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'current_user' => true,
                ]];
            }
        }
    }

    /**
     * Add the current team to the context
     */
    protected function addCurrentTeamContext(array &$result): void
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'currentTeam') && $user->currentTeam) {
            $team = $user->currentTeam;
            $teamClass = get_class($team);

            if (isset($result[$teamClass])) {
                // If we already have teams in the context, make sure the current team is included
                $currentTeamExists = false;
                foreach ($result[$teamClass] as $index => $existingTeam) {
                    if ($existingTeam['id'] === $team->id) {
                        // Mark this team as the current team
                        $result[$teamClass][$index]['current_team'] = true;
                        $currentTeamExists = true;
                        break;
                    }
                }

                if (! $currentTeamExists) {
                    $result[$teamClass][] = $this->formatTeamData($team, true);
                }
            } else {
                // If no teams in context yet, add the current team
                $result[$teamClass] = [$this->formatTeamData($team, true)];
            }

            // Add team members if not already included
            $this->addTeamMembers($result, $team);

            // Add students in the current team
            $this->addTeamStudents($result, $team);

            // Add invited users to the current team
            $this->addTeamInvitations($result, $team);
        }
    }

    /**
     * Format team data for context
     */
    protected function formatTeamData(Team $team, bool $isCurrent = false): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'join_code' => $team->join_code,
            'personal_team' => $team->personal_team,
            'current_team' => $isCurrent,
            'owner_id' => $team->user_id,
        ];
    }

    /**
     * Add team members to the context
     */
    protected function addTeamMembers(array &$result, Team $team): void
    {
        $userClass = User::class;

        // Get team members
        $teamMembers = $team->users;

        if ($teamMembers->isNotEmpty()) {
            $formattedMembers = $teamMembers->map(function ($user) {
                $membership = $user->membership;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $membership->role,
                    'current_user' => Auth::id() === $user->id,
                ];
            })->toArray();

            if (isset($result[$userClass])) {
                // Merge with existing users, avoiding duplicates
                $existingIds = collect($result[$userClass])->pluck('id')->toArray();

                foreach ($formattedMembers as $member) {
                    if (! in_array($member['id'], $existingIds)) {
                        $result[$userClass][] = $member;
                        $existingIds[] = $member['id'];
                    }
                }
            } else {
                $result[$userClass] = $formattedMembers;
            }
        }
    }

    /**
     * Add students in the current team to the context
     */
    protected function addTeamStudents(array &$result, Team $team): void
    {
        $studentClass = Student::class;

        // Get students in the team
        $students = $team->students()->with('user')->get();

        if ($students->isNotEmpty()) {
            $formattedStudents = $students->map(function ($student) {
                $data = [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'student_id' => $student->student_id,
                    'gender' => $student->gender,
                    'status' => $student->status,
                    'team_id' => $student->team_id,
                ];

                // Add user information if the student is linked to a user
                if ($student->user) {
                    $data['user_id'] = $student->user_id;
                    $data['user_name'] = $student->user->name;
                    $data['user_email'] = $student->user->email;
                }

                return $data;
            })->toArray();

            if (isset($result[$studentClass])) {
                // Merge with existing students, avoiding duplicates
                $existingIds = collect($result[$studentClass])->pluck('id')->toArray();

                foreach ($formattedStudents as $student) {
                    if (! in_array($student['id'], $existingIds)) {
                        $result[$studentClass][] = $student;
                        $existingIds[] = $student['id'];
                    }
                }
            } else {
                $result[$studentClass] = $formattedStudents;
            }
        }
    }

    /**
     * Add team invitations to the context
     */
    protected function addTeamInvitations(array &$result, Team $team): void
    {
        // Get the team invitations model class from Jetstream
        $invitationClass = Jetstream::teamInvitationModel();

        // Get pending invitations for the team
        $invitations = $invitationClass::where('team_id', $team->id)->get();

        if ($invitations->isNotEmpty()) {
            $formattedInvitations = $invitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'team_id' => $invitation->team_id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                ];
            })->toArray();

            if (isset($result[$invitationClass])) {
                // Merge with existing invitations, avoiding duplicates
                $existingIds = collect($result[$invitationClass])->pluck('id')->toArray();

                foreach ($formattedInvitations as $invitation) {
                    if (! in_array($invitation['id'], $existingIds)) {
                        $result[$invitationClass][] = $invitation;
                        $existingIds[] = $invitation['id'];
                    }
                }
            } else {
                $result[$invitationClass] = $formattedInvitations;
            }
        }
    }
}
