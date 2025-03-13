<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\ActivitySubmission;
use App\Models\ActivityType;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Database\Seeders\TeacherWorkflowSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user if it doesn't exist
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // Create activity types
        $this->createActivityTypes();

        // TEAM 1: Team with invited users that have student role
        $team1 = $this->createTeamWithInvitedUsers($testUser);

        // TEAM 2: Team with student records but no invited users
        $team2 = $this->createTeamWithStudentRecords($testUser);

        // Set team1 as current team for teacher if not set
        if (!$testUser->current_team_id) {
            $testUser->current_team_id = $team1->id;
            $testUser->save();
        }

        // Seed exams
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
        $students = [];
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
            $student = Student::firstOrCreate(
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

            $students[] = $student;
        }

        // Create activities and submissions for Team 1
        $this->createActivitiesAndSubmissions($team1, $teacher, collect($students));

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
        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $student = Student::firstOrCreate(
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

            $students[] = $student;
        }

        // Create activities and submissions for Team 2
        $this->createActivitiesAndSubmissions($team2, $teacher, collect($students));

        return $team2;
    }

    /**
     * Create activities and submissions for a team
     */
    private function createActivitiesAndSubmissions(Team $team, User $teacher, $students): void
    {
        // Get activity types
        $quizType = ActivityType::where('name', 'Quiz')->first();
        $assignmentType = ActivityType::where('name', 'Assignment')->first();
        $projectType = ActivityType::where('name', 'Project')->first();
        $essayType = ActivityType::where('name', 'Essay')->first();
        $presentationType = ActivityType::where('name', 'Presentation')->first();

        // Create a written activity (Quiz)
        if ($quizType) {
            $quiz = Activity::firstOrCreate(
                [
                    'team_id' => $team->id,
                    'title' => 'Mathematics Quiz',
                ],
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'activity_type_id' => $quizType->id,
                    'title' => 'Mathematics Quiz',
                    'description' => 'A quiz to test basic mathematics knowledge',
                    'instructions' => 'Answer all questions. Each question is worth 5 points.',
                    'format' => 'quiz',
                    'category' => 'written',
                    'mode' => 'individual',
                    'total_points' => 50,
                    'status' => 'published',
                ]
            );

            // Create submissions for this activity
            foreach ($students as $index => $student) {
                $status = match ($index % 4) {
                    0 => 'not_started',
                    1 => 'in_progress',
                    2 => 'submitted',
                    3 => 'graded',
                };

                $score = $status === 'graded' ? rand(0, $quiz->total_points) : null;
                $finalGrade = $score !== null ? ($score / $quiz->total_points) * 100 : null;

                ActivitySubmission::firstOrCreate(
                    [
                        'activity_id' => $quiz->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'activity_id' => $quiz->id,
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' || $status === 'graded' ? 'Completed quiz with multiple-choice answers.' : null,
                        'status' => $status,
                        'score' => $score,
                        'final_grade' => $finalGrade,
                        'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                        'graded_by' => $status === 'graded' ? $teacher->id : null,
                        'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                    ]
                );
            }
        }

        // Create a performance activity (Presentation)
        if ($presentationType) {
            $presentation = Activity::firstOrCreate(
                [
                    'team_id' => $team->id,
                    'title' => 'Science Topic Presentation',
                ],
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'activity_type_id' => $presentationType->id,
                    'title' => 'Science Topic Presentation',
                    'description' => 'Present a science topic to the class',
                    'instructions' => 'Prepare a 5-minute presentation on a science topic of your choice.',
                    'format' => 'presentation',
                    'category' => 'performance',
                    'mode' => 'individual',
                    'total_points' => 50,
                    'status' => 'published',
                ]
            );

            // Create submissions for this activity
            foreach ($students as $index => $student) {
                $status = match ($index % 4) {
                    0 => 'not_started',
                    1 => 'in_progress',
                    2 => 'submitted',
                    3 => 'graded',
                };

                $score = $status === 'graded' ? rand(0, $presentation->total_points) : null;
                $finalGrade = $score !== null ? ($score / $presentation->total_points) * 100 : null;

                ActivitySubmission::firstOrCreate(
                    [
                        'activity_id' => $presentation->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'activity_id' => $presentation->id,
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' || $status === 'graded' ? 'Presentation on renewable energy sources.' : null,
                        'status' => $status,
                        'score' => $score,
                        'final_grade' => $finalGrade,
                        'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                        'graded_by' => $status === 'graded' ? $teacher->id : null,
                        'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                    ]
                );
            }
        }

        // Create a group project with roles
        if ($projectType) {
            $project = Activity::firstOrCreate(
                [
                    'team_id' => $team->id,
                    'title' => 'History Research Project',
                ],
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'activity_type_id' => $projectType->id,
                    'title' => 'History Research Project',
                    'description' => 'Research and document a historical event',
                    'instructions' => 'Work in groups to research a historical event and create a detailed report.',
                    'format' => 'project',
                    'category' => 'written',
                    'mode' => 'group',
                    'total_points' => 150,
                    'status' => 'published',
                ]
            );

            // Create roles for the project
            $roles = [
                ['name' => 'Team Leader', 'description' => 'Coordinates the team and ensures deadlines are met'],
                ['name' => 'Researcher', 'description' => 'Gathers information from various sources'],
                ['name' => 'Writer', 'description' => 'Compiles the research into a coherent document'],
                ['name' => 'Editor', 'description' => 'Reviews and edits the final document'],
            ];

            foreach ($roles as $roleConfig) {
                ActivityRole::firstOrCreate(
                    [
                        'activity_id' => $project->id,
                        'name' => $roleConfig['name'],
                    ],
                    [
                        'activity_id' => $project->id,
                        'name' => $roleConfig['name'],
                        'description' => $roleConfig['description'],
                    ]
                );
            }

            // Create submissions for this activity (for half the students)
            foreach ($students->take(10) as $index => $student) {
                $status = match ($index % 3) {
                    0 => 'in_progress',
                    1 => 'submitted',
                    2 => 'graded',
                };

                $score = $status === 'graded' ? rand(0, $project->total_points) : null;
                $finalGrade = $score !== null ? ($score / $project->total_points) * 100 : null;

                ActivitySubmission::firstOrCreate(
                    [
                        'activity_id' => $project->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'activity_id' => $project->id,
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' || $status === 'graded' ? 'Research project on World War II.' : null,
                        'status' => $status,
                        'score' => $score,
                        'final_grade' => $finalGrade,
                        'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                        'graded_by' => $status === 'graded' ? $teacher->id : null,
                        'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                    ]
                );
            }
        }

        // Create a take-home essay with deadline
        if ($essayType) {
            $essay = Activity::firstOrCreate(
                [
                    'team_id' => $team->id,
                    'title' => 'Research Essay',
                ],
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'activity_type_id' => $essayType->id,
                    'title' => 'Research Essay',
                    'description' => 'Write a research essay on a topic of your choice',
                    'instructions' => 'Write a 1000-word research essay with proper citations. Due in two weeks.',
                    'format' => 'assignment',
                    'category' => 'written',
                    'mode' => 'take_home',
                    'total_points' => 150,
                    'status' => 'published',
                    'deadline' => now()->addDays(14),
                ]
            );

            // Create submissions for this activity
            foreach ($students as $index => $student) {
                $status = match ($index % 5) {
                    0 => 'not_started',
                    1, 2 => 'in_progress',
                    3 => 'submitted',
                    4 => 'graded',
                };

                $score = $status === 'graded' ? rand(0, $essay->total_points) : null;
                $finalGrade = $score !== null ? ($score / $essay->total_points) * 100 : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                ActivitySubmission::firstOrCreate(
                    [
                        'activity_id' => $essay->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'activity_id' => $essay->id,
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' || $status === 'graded' ? 'Research essay on climate change impacts.' : null,
                        'status' => $status,
                        'score' => $score,
                        'final_grade' => $finalGrade,
                        'feedback' => $feedback,
                        'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                        'graded_by' => $status === 'graded' ? $teacher->id : null,
                        'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                    ]
                );
            }
        }

        // Create an assignment
        if ($assignmentType) {
            $assignment = Activity::firstOrCreate(
                [
                    'team_id' => $team->id,
                    'title' => 'English Grammar Assignment',
                ],
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'activity_type_id' => $assignmentType->id,
                    'title' => 'English Grammar Assignment',
                    'description' => 'An assignment to practice English grammar',
                    'instructions' => 'Complete all exercises in the worksheet.',
                    'format' => 'assignment',
                    'category' => 'written',
                    'mode' => 'individual',
                    'total_points' => 100,
                    'status' => 'published',
                ]
            );

            // Create submissions for this activity
            foreach ($students as $index => $student) {
                $status = match ($index % 4) {
                    0 => 'not_started',
                    1 => 'in_progress',
                    2 => 'submitted',
                    3 => 'graded',
                };

                $score = $status === 'graded' ? rand(0, $assignment->total_points) : null;
                $finalGrade = $score !== null ? ($score / $assignment->total_points) * 100 : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                ActivitySubmission::firstOrCreate(
                    [
                        'activity_id' => $assignment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'activity_id' => $assignment->id,
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' || $status === 'graded' ? 'Completed grammar exercises.' : null,
                        'status' => $status,
                        'score' => $score,
                        'final_grade' => $finalGrade,
                        'feedback' => $feedback,
                        'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                        'graded_by' => $status === 'graded' ? $teacher->id : null,
                        'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                    ]
                );
            }
        }
    }

    /**
     * Get random feedback for graded submissions
     */
    private function getRandomFeedback(): string
    {
        $feedbacks = [
            "Excellent work! Your understanding of the concepts is clear.",
            "Good job. There are a few areas that could use improvement.",
            "Satisfactory work, but please pay more attention to details.",
            "You've made some good points, but your analysis needs more depth.",
            "Well-structured and thoughtful. Keep up the good work!",
            "Your work shows promise, but needs more development in key areas.",
            "Very thorough analysis. I'm impressed with your attention to detail.",
            "You've met the basic requirements, but could have expanded more on your ideas.",
            "Strong start, but your conclusion needs more supporting evidence.",
            "Outstanding work! Your critical thinking skills are evident throughout."
        ];

        return $feedbacks[array_rand($feedbacks)];
    }
}

// Helper function to randomly select an item from an array
if (!function_exists('array_random')) {
    function array_random($array) {
        return $array[array_rand($array)];
    }
}
