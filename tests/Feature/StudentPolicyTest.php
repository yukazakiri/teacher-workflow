<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_students(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create a teacher user (team owner)
        $teacher = User::factory()->create();
        $teacher->teams()->attach($team, ['role' => 'teacher']);
        $teacher->switchTeam($team);
        
        // Create a student
        $student = Student::factory()->create([
            'team_id' => $team->id,
        ]);

        // Check policy authorization
        $this->assertTrue($teacher->can('view', $student));
        $this->assertTrue($teacher->can('update', $student));
        $this->assertTrue($teacher->can('delete', $student));
        $this->assertTrue($teacher->can('viewAttendance', $student));
        $this->assertTrue($teacher->can('viewProgress', $student));
    }

    public function test_student_can_only_view_and_update_themselves(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create student user
        $studentUser = User::factory()->create();
        $studentUser->teams()->attach($team, ['role' => 'student']);
        $studentUser->switchTeam($team);
        
        // Create student records
        $ownStudent = Student::factory()->create([
            'team_id' => $team->id,
            'user_id' => $studentUser->id,
        ]);
        
        $otherStudent = Student::factory()->create([
            'team_id' => $team->id,
        ]);

        // Self-access checks
        $this->assertTrue($studentUser->can('view', $ownStudent));
        $this->assertTrue($studentUser->can('update', $ownStudent));
        $this->assertTrue($studentUser->can('viewAttendance', $ownStudent));
        $this->assertTrue($studentUser->can('viewProgress', $ownStudent));
        
        // Cannot delete even themselves
        $this->assertFalse($studentUser->can('delete', $ownStudent));
        
        // Cannot access other students
        $this->assertFalse($studentUser->can('view', $otherStudent));
        $this->assertFalse($studentUser->can('update', $otherStudent));
        $this->assertFalse($studentUser->can('viewAttendance', $otherStudent));
        $this->assertFalse($studentUser->can('viewProgress', $otherStudent));
    }
    
    public function test_only_team_owner_can_manage_user_links(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create a team owner
        $owner = User::factory()->create();
        $team->users()->attach($owner, ['role' => 'teacher']);
        $owner->switchTeam($team);
        $team->owner_id = $owner->id;
        $team->save();
        
        // Create a teacher (not owner)
        $teacher = User::factory()->create();
        $teacher->teams()->attach($team, ['role' => 'teacher']);
        $teacher->switchTeam($team);
        
        // Create a student
        $student = Student::factory()->create([
            'team_id' => $team->id,
        ]);
        
        // Owner can manage user links
        $this->assertTrue($owner->can('manageUserLinks', $student));
        
        // Teacher (not owner) cannot manage user links
        $this->assertFalse($teacher->can('manageUserLinks', $student));
        
        // Student cannot manage user links
        $studentUser = User::factory()->create();
        $studentUser->teams()->attach($team, ['role' => 'student']);
        $studentUser->switchTeam($team);
        $this->assertFalse($studentUser->can('manageUserLinks', $student));
    }
} 