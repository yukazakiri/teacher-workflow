<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\ActivityType;
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
        // Check if the test user already exists
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->withPersonalTeam()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password')
            ]);
        }

        $this->call(TeacherWorkflowSeeder::class);

        // Create a teacher user with a team
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // Create or get the teacher's team
        $team = Team::firstOrCreate(
            ['user_id' => $teacher->id],
            [
                'name' => 'Teacher Classroom',
                'user_id' => $teacher->id,
                'personal_team' => false,
            ]
        );

        // Set current team for teacher
        $teacher->current_team_id = $team->id;
        $teacher->save();

        // Create 20 student users
        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $student = User::firstOrCreate(
                ['email' => "student{$i}@example.com"],
                [
                    'name' => "Student {$i}",
                    'email' => "student{$i}@example.com",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
            $students[] = $student;

            // Add student to team if not already a member
            if (!$team->hasUser($student)) {
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

        $createdTypes = [];
        foreach ($activityTypes as $type) {
            $activityType = ActivityType::firstOrCreate(
                ['name' => $type['name']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                ]
            );
            $createdTypes[] = $activityType;
        }

        // Define activity configurations to ensure we cover all combinations
        $activityConfigurations = [
            // Individual Written Activities
            [
                'type' => 'Quiz',
                'mode' => 'individual',
                'category' => 'written',
                'format' => 'quiz',
                'title' => 'Mathematics Quiz',
                'description' => 'A quiz to test basic mathematics knowledge',
                'instructions' => 'Answer all questions. Each question is worth 5 points.',
                'total_points' => 50,
                'status' => 'published',
            ],
            [
                'type' => 'Assignment',
                'mode' => 'individual',
                'category' => 'written',
                'format' => 'assignment',
                'title' => 'English Grammar Assignment',
                'description' => 'An assignment to practice English grammar',
                'instructions' => 'Complete all exercises in the worksheet.',
                'total_points' => 100,
                'status' => 'published',
            ],
            [
                'type' => 'Essay',
                'mode' => 'individual',
                'category' => 'written',
                'format' => 'assignment',
                'title' => 'Reflective Essay',
                'description' => 'Write a reflective essay about your learning experience',
                'instructions' => 'Write a 500-word essay reflecting on what you have learned this semester.',
                'total_points' => 100,
                'status' => 'draft',
            ],

            // Individual Performance Activities
            [
                'type' => 'Presentation',
                'mode' => 'individual',
                'category' => 'performance',
                'format' => 'presentation',
                'title' => 'Science Topic Presentation',
                'description' => 'Present a science topic to the class',
                'instructions' => 'Prepare a 5-minute presentation on a science topic of your choice.',
                'total_points' => 50,
                'status' => 'published',
            ],
            [
                'type' => 'Lab Work',
                'mode' => 'individual',
                'category' => 'performance',
                'format' => 'project',
                'title' => 'Chemistry Lab Experiment',
                'description' => 'Conduct a chemistry experiment and document results',
                'instructions' => 'Follow the lab procedure and document your observations and results.',
                'total_points' => 75,
                'status' => 'published',
            ],

            // Group Written Activities
            [
                'type' => 'Project',
                'mode' => 'group',
                'category' => 'written',
                'format' => 'project',
                'title' => 'History Research Project',
                'description' => 'Research and document a historical event',
                'instructions' => 'Work in groups to research a historical event and create a detailed report.',
                'total_points' => 150,
                'status' => 'published',
                'roles' => [
                    ['name' => 'Team Leader', 'description' => 'Coordinates the team and ensures deadlines are met'],
                    ['name' => 'Researcher', 'description' => 'Gathers information from various sources'],
                    ['name' => 'Writer', 'description' => 'Compiles the research into a coherent document'],
                    ['name' => 'Editor', 'description' => 'Reviews and edits the final document'],
                ],
            ],
            [
                'type' => 'Discussion',
                'mode' => 'group',
                'category' => 'written',
                'format' => 'discussion',
                'title' => 'Literature Discussion',
                'description' => 'Group discussion on a literary work',
                'instructions' => 'Discuss the themes, characters, and plot of the assigned literary work.',
                'total_points' => 50,
                'status' => 'published',
                'roles' => [
                    ['name' => 'Moderator', 'description' => 'Facilitates the discussion and ensures everyone participates'],
                    ['name' => 'Note Taker', 'description' => 'Records key points from the discussion'],
                    ['name' => 'Timekeeper', 'description' => 'Ensures the discussion stays on schedule'],
                ],
            ],

            // Group Performance Activities
            [
                'type' => 'Presentation',
                'mode' => 'group',
                'category' => 'performance',
                'format' => 'presentation',
                'title' => 'Group Debate',
                'description' => 'Debate on a controversial topic',
                'instructions' => 'Prepare arguments for and against the given topic and present them in a structured debate.',
                'total_points' => 100,
                'status' => 'published',
                'roles' => [
                    ['name' => 'Opening Speaker', 'description' => 'Presents the opening argument'],
                    ['name' => 'Rebuttal Speaker', 'description' => 'Responds to the opposing team\'s arguments'],
                    ['name' => 'Closing Speaker', 'description' => 'Summarizes the team\'s position and makes the final appeal'],
                    ['name' => 'Researcher', 'description' => 'Gathers evidence to support the team\'s arguments'],
                ],
            ],
            [
                'type' => 'Project',
                'mode' => 'group',
                'category' => 'performance',
                'format' => 'project',
                'title' => 'Science Fair Project',
                'description' => 'Create and present a science fair project',
                'instructions' => 'Design, execute, and present a science experiment for the school science fair.',
                'total_points' => 200,
                'status' => 'draft',
                'roles' => [
                    ['name' => 'Project Manager', 'description' => 'Oversees the project and ensures all components are completed'],
                    ['name' => 'Experimenter', 'description' => 'Conducts the experiment and records data'],
                    ['name' => 'Analyst', 'description' => 'Analyzes the data and draws conclusions'],
                    ['name' => 'Presenter', 'description' => 'Creates the presentation materials and presents the project'],
                ],
            ],

            // Take-Home Written Activities
            [
                'type' => 'Assignment',
                'mode' => 'take_home',
                'category' => 'written',
                'format' => 'assignment',
                'title' => 'Mathematics Problem Set',
                'description' => 'A set of advanced mathematics problems',
                'instructions' => 'Solve all problems and show your work. Due in one week.',
                'total_points' => 100,
                'status' => 'published',
                'deadline' => now()->addDays(7),
            ],
            [
                'type' => 'Essay',
                'mode' => 'take_home',
                'category' => 'written',
                'format' => 'assignment',
                'title' => 'Research Essay',
                'description' => 'Write a research essay on a topic of your choice',
                'instructions' => 'Write a 1000-word research essay with proper citations. Due in two weeks.',
                'total_points' => 150,
                'status' => 'published',
                'deadline' => now()->addDays(14),
            ],

            // Take-Home Performance Activities
            [
                'type' => 'Project',
                'mode' => 'take_home',
                'category' => 'performance',
                'format' => 'project',
                'title' => 'Art Portfolio',
                'description' => 'Create an art portfolio showcasing different techniques',
                'instructions' => 'Create 5 pieces of art using different techniques. Document your process.',
                'total_points' => 150,
                'status' => 'published',
                'deadline' => now()->addDays(21),
            ],
            [
                'type' => 'Presentation',
                'mode' => 'take_home',
                'category' => 'performance',
                'format' => 'presentation',
                'title' => 'Video Presentation',
                'description' => 'Create a video presentation on a historical figure',
                'instructions' => 'Create a 5-minute video presentation about a historical figure of your choice.',
                'total_points' => 100,
                'status' => 'draft',
                'deadline' => now()->addDays(10),
            ],

            // Additional activities to ensure all types are covered
            [
                'type' => 'Exam',
                'mode' => 'individual',
                'category' => 'written',
                'format' => 'quiz',
                'title' => 'Final Exam',
                'description' => 'Comprehensive exam covering all course material',
                'instructions' => 'Answer all questions. The exam is worth 200 points total.',
                'total_points' => 200,
                'status' => 'draft',
            ],
            [
                'type' => 'Lab Work',
                'mode' => 'group',
                'category' => 'performance',
                'format' => 'project',
                'title' => 'Physics Lab Experiment',
                'description' => 'Conduct a physics experiment in groups',
                'instructions' => 'Follow the lab procedure, collect data, and write a lab report.',
                'total_points' => 100,
                'status' => 'published',
                'roles' => [
                    ['name' => 'Equipment Manager', 'description' => 'Sets up and manages the lab equipment'],
                    ['name' => 'Data Collector', 'description' => 'Records measurements and observations'],
                    ['name' => 'Analyst', 'description' => 'Analyzes the data and performs calculations'],
                    ['name' => 'Report Writer', 'description' => 'Compiles the lab report'],
                ],
            ],
        ];

        // Create activities based on configurations
        foreach ($activityConfigurations as $config) {
            // Find the activity type
            $activityType = ActivityType::where('name', $config['type'])->first();

            if (!$activityType) {
                continue; // Skip if activity type not found
            }

            // Create the activity
            $activity = Activity::firstOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'title' => $config['title'],
                ],
                [
                    'teacher_id' => $teacher->id,
                    'team_id' => $team->id,
                    'activity_type_id' => $activityType->id,
                    'title' => $config['title'],
                    'description' => $config['description'],
                    'instructions' => $config['instructions'],
                    'format' => $config['format'],
                    'category' => $config['category'],
                    'mode' => $config['mode'],
                    'total_points' => $config['total_points'],
                    'status' => $config['status'],
                    'deadline' => $config['deadline'] ?? null,
                ]
            );

            // Create roles for group activities
            if ($config['mode'] === 'group' && isset($config['roles'])) {
                foreach ($config['roles'] as $roleConfig) {
                    ActivityRole::firstOrCreate(
                        [
                            'activity_id' => $activity->id,
                            'name' => $roleConfig['name'],
                        ],
                        [
                            'activity_id' => $activity->id,
                            'name' => $roleConfig['name'],
                            'description' => $roleConfig['description'],
                        ]
                    );
                }
            }

            // Create submissions for published activities
            if ($activity->isPublished()) {
                foreach ($students as $student) {
                    // Only create submission if it doesn't exist
                    $submissionExists = $activity->submissions()
                        ->where('student_id', $student->id)
                        ->exists();

                    if (!$submissionExists) {
                        $activity->submissions()->create([
                            'student_id' => $student->id,
                            'content' => 'This is a sample submission content.',
                            'status' => array_random(['not_started', 'in_progress', 'submitted', 'graded']),
                            'score' => rand(0, $activity->total_points),
                        ]);
                    }
                }
            }
        }
    }
}

// Helper function to randomly select an item from an array
if (!function_exists('array_random')) {
    function array_random($array) {
        return $array[array_rand($array)];
    }
}
