<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Student;
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

        // Create activity types if they don't exist
        $this->createActivityTypes();

        // Create two teams for the teacher
        $team1 = $this->createTeamWithInvitedUsers($teacher);
        $team2 = $this->createTeamWithStudentRecords($teacher);

        // Set team1 as current team for teacher if not set
        if (!$teacher->current_team_id) {
            $teacher->current_team_id = $team1->id;
            $teacher->save();
        }

        // Seed activities for both teams
        $this->call(ActivitySeeder::class);

        // Seed exams for both teams
        $this->call(ExamSeeder::class);
    }

    /**
     * Create activity types
     */
    private function createActivityTypes(): void
    {
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
    }

    /**
     * Create a team with invited users that have student role
     *
     * @param User $teacher The teacher user
     * @return Team The created team
     */
    private function createTeamWithInvitedUsers(User $teacher): Team
    {
        // Create Team 1
        $team1 = Team::firstOrCreate(
            [
                'name' => 'Classroom with Invited Students',
                'user_id' => $teacher->id,
            ],
            [
                'name' => 'Classroom with Invited Students',
                'user_id' => $teacher->id,
                'personal_team' => false,
            ]
        );

        // Create 20 student users and invite them to the team
        for ($i = 1; $i <= 20; $i++) {
            $studentUser = User::firstOrCreate(
                ['email' => "invited_student{$i}@example.com"],
                [
                    'name' => "Invited Student {$i}",
                    'email' => "invited_student{$i}@example.com",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );

            // Create invitation if not exists
            TeamInvitation::firstOrCreate(
                [
                    'team_id' => $team1->id,
                    'email' => $studentUser->email,
                ],
                [
                    'team_id' => $team1->id,
                    'email' => $studentUser->email,
                    'role' => 'student',
                ]
            );

            // Add user to team if not already a member
            if (!$team1->hasUser($studentUser)) {
                $team1->users()->attach(
                    $studentUser,
                    ['role' => 'student']
                );
            }

            // Auto-create student record if not exists
            Student::firstOrCreate(
                [
                    'email' => $studentUser->email,
                    'team_id' => $team1->id,
                ],
                [
                    'name' => $studentUser->name,
                    'email' => $studentUser->email,
                    'team_id' => $team1->id,
                    'status' => 'active',
                    'user_id' => $studentUser->id,
                    'student_id' => 'ST' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'birth_date' => now()->subYears(rand(18, 25))->subDays(rand(1, 365)),
                ]
            );
        }

        return $team1;
    }

    /**
     * Create a team with student records but no invited users
     *
     * @param User $teacher The teacher user
     * @return Team The created team
     */
    private function createTeamWithStudentRecords(User $teacher): Team
    {
        // Create Team 2
        $team2 = Team::firstOrCreate(
            [
                'name' => 'Classroom with Student Records Only',
                'user_id' => $teacher->id,
            ],
            [
                'name' => 'Classroom with Student Records Only',
                'user_id' => $teacher->id,
                'personal_team' => false,
            ]
        );

        // Create 20 student records without user accounts
        for ($i = 1; $i <= 20; $i++) {
            Student::firstOrCreate(
                [
                    'email' => "record_student{$i}@example.com",
                    'team_id' => $team2->id,
                ],
                [
                    'name' => "Record Student {$i}",
                    'email' => "record_student{$i}@example.com",
                    'team_id' => $team2->id,
                    'status' => 'active',
                    'user_id' => null, // No associated user
                    'student_id' => 'RS' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'birth_date' => now()->subYears(rand(18, 25))->subDays(rand(1, 365)),
                    'notes' => $i % 5 === 0 ? 'Transfer student' : null,
                ]
            );
        }

        return $team2;
    }
}
