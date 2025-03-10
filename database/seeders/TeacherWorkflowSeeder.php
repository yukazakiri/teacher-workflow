<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test teacher if not exists
        $teacher = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // Create or get the teacher's team
        $team = Team::firstOrCreate(
            ['user_id' => $teacher->id],
            [
                'name' => 'Test Classroom',
                'user_id' => $teacher->id,
                'personal_team' => false,
            ]
        );

        // Set current team for teacher if not set
        if (!$teacher->current_team_id) {
            $teacher->current_team_id = $team->id;
            $teacher->save();
        }

        // Create 20 student users if they don't exist
        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $student = User::firstOrCreate(
                ['email' => "student{$i}@test.com"],
                [
                    'name' => "Student {$i}",
                    'email' => "student{$i}@test.com",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
            $students[] = $student;

            // Add student to team if not already a member
            if (!$team->hasUser($student)) {
                // Create invitation
                TeamInvitation::firstOrCreate(
                    [
                        'team_id' => $team->id,
                        'email' => $student->email,
                    ],
                    [
                        'team_id' => $team->id,
                        'email' => $student->email,
                        'role' => 'student',
                    ]
                );

                // Add to team
                $team->users()->attach(
                    $student,
                    ['role' => 'student']
                );
            }
        }

        // Create activity types if they don't exist
        $activityTypes = [
            ['name' => 'Quiz', 'description' => 'Short assessment to test knowledge'],
            ['name' => 'Assignment', 'description' => 'Task to be completed by students'],
            ['name' => 'Project', 'description' => 'Larger task requiring planning and execution'],
            ['name' => 'Presentation', 'description' => 'Oral presentation of a topic'],
            ['name' => 'Discussion', 'description' => 'Group discussion on a topic'],
            ['name' => 'Lab Work', 'description' => 'Hands-on laboratory activity'],
            ['name' => 'Essay', 'description' => 'Written composition on a specific topic'],
            ['name' => 'Exam', 'description' => 'Formal assessment of knowledge'],
        ];

        foreach ($activityTypes as $type) {
            ActivityType::firstOrCreate(
                ['name' => $type['name']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                ]
            );
        }

        // Call the ActivitySeeder to create specific activities
        $this->call(ActivitySeeder::class);
    }
}
