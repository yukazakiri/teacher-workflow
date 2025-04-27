<?php

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;
use App\Models\Student;
use App\Helpers\StudentHelper;
use Laravel\Jetstream\Events\TeamMemberAdded;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
        
        // Listen for team member added event to create student record
        $this->listenForTeamEvents();
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::role('teacher', 'Teacher', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Teachers can perform any action.');

        Jetstream::role('student', 'Student', [
            'read',
            'create',
            'update',
        ])->description('Student users have the ability to read, create, and update.');

        Jetstream::role('parent', 'Parent', [
            'read',
            'chat',
        ])->description('Parents can view student data and chat with teachers.');
    }

    /**
     * Setup listeners for team events
     */
    protected function listenForTeamEvents(): void
    {
        // When a user is added to a team, create a student record
        \Illuminate\Support\Facades\Event::listen(TeamMemberAdded::class, function (TeamMemberAdded $event): void {
            StudentHelper::createStudentRecord($event->user, $event->team);
        });
    }
    
    /**
     * Create a student record for the user in the team
     */
    protected function createStudentRecord($team, $user): void
    {
        StudentHelper::createStudentRecord($user, $team);
    }
}
