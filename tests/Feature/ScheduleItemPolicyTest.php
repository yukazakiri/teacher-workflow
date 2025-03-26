<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ScheduleItem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_owner_has_full_access_to_schedule_items(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create an owner user
        $owner = User::factory()->create();
        $team->users()->attach($owner, ['role' => 'teacher']);
        $owner->switchTeam($team);
        $team->owner_id = $owner->id;
        $team->save();
        
        // Create a schedule item
        $scheduleItem = ScheduleItem::factory()->create([
            'team_id' => $team->id,
        ]);

        // Check policy authorization
        $this->assertTrue($owner->can('viewAny', ScheduleItem::class));
        $this->assertTrue($owner->can('view', $scheduleItem));
        $this->assertTrue($owner->can('create', ScheduleItem::class));
        $this->assertTrue($owner->can('update', $scheduleItem));
        $this->assertTrue($owner->can('delete', $scheduleItem));
    }

    public function test_teacher_can_manage_but_not_delete_schedule_items(): void
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
        
        // Create a schedule item
        $scheduleItem = ScheduleItem::factory()->create([
            'team_id' => $team->id,
        ]);

        // Check policy authorization for teacher
        $this->assertTrue($teacher->can('viewAny', ScheduleItem::class));
        $this->assertTrue($teacher->can('view', $scheduleItem));
        $this->assertTrue($teacher->can('create', ScheduleItem::class));
        $this->assertTrue($teacher->can('update', $scheduleItem));
        
        // Teachers cannot delete schedule items
        $this->assertFalse($teacher->can('delete', $scheduleItem));
    }

    public function test_student_can_view_but_not_manage_schedule_items(): void
    {
        // Create a team
        $team = Team::factory()->create();
        
        // Create student user
        $studentUser = User::factory()->create();
        $studentUser->teams()->attach($team, ['role' => 'student']);
        $studentUser->switchTeam($team);
        
        // Create a schedule item
        $scheduleItem = ScheduleItem::factory()->create([
            'team_id' => $team->id,
        ]);
        
        // Student can view schedule items
        $this->assertTrue($studentUser->can('viewAny', ScheduleItem::class));
        $this->assertTrue($studentUser->can('view', $scheduleItem));
        
        // Student cannot manage schedule items
        $this->assertFalse($studentUser->can('create', ScheduleItem::class));
        $this->assertFalse($studentUser->can('update', $scheduleItem));
        $this->assertFalse($studentUser->can('delete', $scheduleItem));
    }
} 