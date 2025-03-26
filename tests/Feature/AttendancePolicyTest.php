<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_owner_has_full_access_to_attendance(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create an owner user
        $owner = User::factory()->create();
        $team->users()->attach($owner, ['role' => 'teacher']);
        $owner->switchTeam($team);
        $team->owner_id = $owner->id;
        $team->save();
        
        // Create a student
        $student = Student::factory()->create([
            'team_id' => $team->id,
        ]);
        
        // Create an attendance record
        $attendance = Attendance::factory()->create([
            'team_id' => $team->id,
            'student_id' => $student->id,
        ]);

        // Check policy authorization
        $this->assertTrue($owner->can('viewAny', Attendance::class));
        $this->assertTrue($owner->can('view', $attendance));
        $this->assertTrue($owner->can('create', Attendance::class));
        $this->assertTrue($owner->can('update', $attendance));
        $this->assertTrue($owner->can('delete', $attendance));
        $this->assertTrue($owner->can('updateStatus', $attendance));
    }

    public function test_teacher_can_manage_but_not_delete_attendance(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create an owner
        $owner = User::factory()->create();
        $team->users()->attach($owner, ['role' => 'teacher']);
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
        
        // Create an attendance record
        $attendance = Attendance::factory()->create([
            'team_id' => $team->id,
            'student_id' => $student->id,
        ]);

        // Check policy authorization for teacher
        $this->assertTrue($teacher->can('viewAny', Attendance::class));
        $this->assertTrue($teacher->can('view', $attendance));
        $this->assertTrue($teacher->can('create', Attendance::class));
        $this->assertTrue($teacher->can('update', $attendance));
        $this->assertTrue($teacher->can('updateStatus', $attendance));
        
        // Teachers cannot delete attendance records
        $this->assertFalse($teacher->can('delete', $attendance));
    }

    public function test_student_can_only_view_own_attendance(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create student users
        $studentUser1 = User::factory()->create();
        $studentUser1->teams()->attach($team, ['role' => 'student']);
        $studentUser1->switchTeam($team);
        
        $studentUser2 = User::factory()->create();
        $studentUser2->teams()->attach($team, ['role' => 'student']);
        
        // Create student records
        $student1 = Student::factory()->create([
            'team_id' => $team->id,
            'user_id' => $studentUser1->id,
        ]);
        
        $student2 = Student::factory()->create([
            'team_id' => $team->id,
            'user_id' => $studentUser2->id,
        ]);
        
        // Create attendance records
        $ownAttendance = Attendance::factory()->create([
            'team_id' => $team->id,
            'student_id' => $student1->id,
        ]);
        
        $otherAttendance = Attendance::factory()->create([
            'team_id' => $team->id,
            'student_id' => $student2->id,
        ]);

        // Student can view own attendance
        $this->assertTrue($studentUser1->can('viewAny', Attendance::class));
        $this->assertTrue($studentUser1->can('view', $ownAttendance));
        
        // Student cannot view other's attendance
        $this->assertFalse($studentUser1->can('view', $otherAttendance));
        
        // Students cannot create, update, or delete attendance
        $this->assertFalse($studentUser1->can('create', Attendance::class));
        $this->assertFalse($studentUser1->can('update', $ownAttendance));
        $this->assertFalse($studentUser1->can('delete', $ownAttendance));
        $this->assertFalse($studentUser1->can('updateStatus', $ownAttendance));
    }
} 