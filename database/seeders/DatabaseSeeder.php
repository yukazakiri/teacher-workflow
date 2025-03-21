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
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create teacher account
        $teacher = $this->createTeacherAccount();

        // Create activity types
        $this->createActivityTypes();

        // Create first classroom with invited users
        $classroomWithInvitedStudents = $this->createClassroomWithInvitedUsers($teacher);

        // Create second classroom with unlinked student records
        $classroomWithUnlinkedStudents = $this->createClassroomWithUnlinkedStudents($teacher);

        // Set default team for teacher if not already set
        if (!$teacher->current_team_id) {
            $teacher->current_team_id = $classroomWithInvitedStudents->id;
            $teacher->save();
        }

        // Seed exams
        $this->call(ExamSeeder::class);
    }

    /**
     * Create teacher account
     *
     * @return User
     */
    private function createTeacherAccount(): User
    {
        return User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
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
                ['description' => $type['description']]
            );
        }
    }

    /**
     * Create a classroom with invited student users that are properly linked
     *
     * @param User $teacher
     * @return Team
     */
    private function createClassroomWithInvitedUsers(User $teacher): Team
    {
        // Create or retrieve team
        $classroom = Team::firstOrCreate(
            [
                'name' => 'Biology 101',
                'user_id' => $teacher->id,
            ],
            [
                'personal_team' => false,
                'id' => Str::uuid(),
                'join_code' => 'BIO101', // Custom join code for easier testing
            ]
        );

        // Create student accounts and link them to the classroom
        $studentUsers = [];
        $studentNames = $this->getRealisticStudentNames();

        for ($i = 0; $i < 20; $i++) {
            $studentName = $studentNames[$i];
            $email = $this->generateStudentEmail($studentName);

            // Create user account
            $studentUser = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $studentName,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );

            $studentUsers[] = $studentUser;

            // Ensure user is a team member
            if (!$studentUser->belongsToTeam($classroom)) {
                // Create invitation if not exists
                TeamInvitation::firstOrCreate(
                    [
                        'team_id' => $classroom->id,
                        'email' => $studentUser->email,
                    ],
                    [
                        'role' => 'student',
                    ]
                );

                // Add user to team (simulate accepted invitation)
                DB::table('team_user')->updateOrInsert(
                    [
                        'team_id' => $classroom->id,
                        'user_id' => $studentUser->id,
                    ],
                    [
                        'role' => 'student',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Set this classroom as the student's current team if not already set
                if (!$studentUser->current_team_id) {
                    $studentUser->current_team_id = $classroom->id;
                    $studentUser->save();
                }
            }

            // Create linked student record if not exists
            $student = Student::firstOrCreate(
                [
                    'team_id' => $classroom->id,
                    'user_id' => $studentUser->id,
                ],
                [
                    'name' => $studentUser->name,
                    'email' => $studentUser->email,
                    'status' => 'active',
                    'student_id' => 'BIO' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'birth_date' => now()->subYears(rand(18, 22))->subDays(rand(1, 365)),
                    'notes' => $this->getRandomStudentNote($i),
                ]
            );

            $studentUsers[$i] = ['user' => $studentUser, 'student' => $student];
        }

        // Create sample activities and submissions for this classroom
        $this->createClassroomActivities($classroom, $teacher, collect($studentUsers));

        return $classroom;
    }

    /**
     * Create a classroom with student records that are not linked to user accounts
     *
     * @param User $teacher
     * @return Team
     */
    private function createClassroomWithUnlinkedStudents(User $teacher): Team
    {
        // Create or retrieve team
        $classroom = Team::firstOrCreate(
            [
                'name' => 'History 101',
                'user_id' => $teacher->id,
            ],
            [
                'personal_team' => false,
                'id' => Str::uuid(),
                'join_code' => 'HIS101', // Custom join code for easier testing
            ]
        );

        // Create unlinked student records
        $students = [];
        $studentNames = $this->getRealisticStudentNames(30); // Get different names

        for ($i = 0; $i < 20; $i++) {
            $studentName = $studentNames[$i];
            $email = $this->generateStudentEmail($studentName);

            // Create student record without linking to a user
            $student = Student::firstOrCreate(
                [
                    'team_id' => $classroom->id,
                    'email' => $email,
                ],
                [
                    'name' => $studentName,
                    'status' => 'active',
                    'user_id' => null, // Explicitly no user link
                    'student_id' => 'HIS' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'birth_date' => now()->subYears(rand(18, 22))->subDays(rand(1, 365)),
                    'notes' => $this->getRandomStudentNote($i),
                ]
            );

            $students[] = $student;
        }

        // Create sample activities and submissions for this classroom
        $this->createClassroomActivities($classroom, $teacher, collect($students));

        return $classroom;
    }

    /**
     * Create activities and submissions for a classroom
     *
     * @param Team $classroom The classroom team
     * @param User $teacher The teacher
     * @param \Illuminate\Support\Collection $students Collection of students or user/student pairs
     */
    private function createClassroomActivities(Team $classroom, User $teacher, $students): void
    {
        // Get activity types
        $quizType = ActivityType::where('name', 'Quiz')->first();
        $assignmentType = ActivityType::where('name', 'Assignment')->first();
        $projectType = ActivityType::where('name', 'Project')->first();
        $essayType = ActivityType::where('name', 'Essay')->first();
        $presentationType = ActivityType::where('name', 'Presentation')->first();

        // Create classroom activities
        $this->createQuizActivity($classroom, $teacher, $students, $quizType);
        $this->createPresentationActivity($classroom, $teacher, $students, $presentationType);
        $this->createGroupProject($classroom, $teacher, $students, $projectType);
        $this->createEssayActivity($classroom, $teacher, $students, $essayType);
        $this->createAssignmentActivity($classroom, $teacher, $students, $assignmentType);
    }

    /**
     * Create a quiz activity and submissions
     */
    private function createQuizActivity(Team $classroom, User $teacher, $students, $quizType): void
    {
        if (!$quizType) return;

        $quiz = Activity::firstOrCreate(
            [
                'team_id' => $classroom->id,
                'title' => 'Unit 1 Quiz',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $classroom->id,
                'activity_type_id' => $quizType->id,
                'description' => 'Quiz covering the first unit of material',
                'instructions' => 'Answer all questions. Each question is worth 5 points.',
                'format' => 'quiz',
                'category' => 'written',
                'mode' => 'individual',
                'total_points' => 50,
                'status' => 'published',
            ]
        );

        // Create submissions for this activity
        $this->createActivitySubmissions($quiz, $students, $teacher);
    }

    /**
     * Create a presentation activity and submissions
     */
    private function createPresentationActivity(Team $classroom, User $teacher, $students, $presentationType): void
    {
        if (!$presentationType) return;

        $presentation = Activity::firstOrCreate(
            [
                'team_id' => $classroom->id,
                'title' => 'Topic Presentation',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $classroom->id,
                'activity_type_id' => $presentationType->id,
                'description' => 'Present a topic related to our current unit',
                'instructions' => 'Prepare a 5-minute presentation with visual aids.',
                'format' => 'presentation',
                'category' => 'performance',
                'mode' => 'individual',
                'total_points' => 50,
                'status' => 'published',
            ]
        );

        // Create submissions for this activity
        $this->createActivitySubmissions($presentation, $students, $teacher);
    }

    /**
     * Create a group project with roles
     */
    private function createGroupProject(Team $classroom, User $teacher, $students, $projectType): void
    {
        if (!$projectType) return;

        $project = Activity::firstOrCreate(
            [
                'team_id' => $classroom->id,
                'title' => 'Research Project',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $classroom->id,
                'activity_type_id' => $projectType->id,
                'description' => 'Collaborative research project',
                'instructions' => 'Work in groups to create a comprehensive research paper and presentation.',
                'format' => 'project',
                'category' => 'written',
                'mode' => 'group',
                'total_points' => 150,
                'status' => 'published',
            ]
        );

        // Create roles for the project
        $roles = [
            ['name' => 'Team Leader', 'description' => 'Coordinates team efforts and ensures deadlines are met'],
            ['name' => 'Researcher', 'description' => 'Gathers information from various sources'],
            ['name' => 'Writer', 'description' => 'Compiles research into a coherent document'],
            ['name' => 'Editor', 'description' => 'Reviews and edits the final document'],
        ];

        foreach ($roles as $roleData) {
            ActivityRole::firstOrCreate(
                [
                    'activity_id' => $project->id,
                    'name' => $roleData['name'],
                ],
                [
                    'description' => $roleData['description'],
                ]
            );
        }

        // Create submissions for half the students
        $this->createActivitySubmissions($project, $students->take(10), $teacher);
    }

    /**
     * Create an essay activity with deadline
     */
    private function createEssayActivity(Team $classroom, User $teacher, $students, $essayType): void
    {
        if (!$essayType) return;

        $essay = Activity::firstOrCreate(
            [
                'team_id' => $classroom->id,
                'title' => 'Analytical Essay',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $classroom->id,
                'activity_type_id' => $essayType->id,
                'description' => 'Analysis of a key topic from our current unit',
                'instructions' => 'Write a 1000-word analytical essay with proper citations.',
                'format' => 'assignment',
                'category' => 'written',
                'mode' => 'take_home',
                'total_points' => 100,
                'status' => 'published',
                'deadline' => now()->addDays(14),
            ]
        );

        // Create submissions
        $this->createActivitySubmissions($essay, $students, $teacher);
    }

    /**
     * Create an assignment activity
     */
    private function createAssignmentActivity(Team $classroom, User $teacher, $students, $assignmentType): void
    {
        if (!$assignmentType) return;

        $assignment = Activity::firstOrCreate(
            [
                'team_id' => $classroom->id,
                'title' => 'Worksheet Assignment',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $classroom->id,
                'activity_type_id' => $assignmentType->id,
                'description' => 'Practice exercises based on recent lessons',
                'instructions' => 'Complete all exercises in the provided worksheet.',
                'format' => 'assignment',
                'category' => 'written',
                'mode' => 'individual',
                'total_points' => 75,
                'status' => 'published',
            ]
        );

        // Create submissions
        $this->createActivitySubmissions($assignment, $students, $teacher);
    }

    /**
     * Create activity submissions for students
     *
     * @param Activity $activity
     * @param \Illuminate\Support\Collection $students
     * @param User $teacher
     */
    private function createActivitySubmissions($activity, $students, $teacher): void
    {
        $submissionStatuses = ['not_started', 'in_progress', 'submitted', 'graded'];
        $contents = [
            'quiz' => 'Completed multiple choice and short answer questions.',
            'presentation' => 'Presented slides and delivered oral presentation on the topic.',
            'project' => 'Compiled research findings into a comprehensive report.',
            'assignment' => 'Completed all required exercises and problems.',
            'essay' => 'Analyzed the topic with supporting evidence and proper citations.'
        ];

        foreach ($students as $index => $studentData) {
            // Handle both student objects and user/student pairs
            $student = isset($studentData['student']) ? $studentData['student'] : $studentData;

            // Determine submission status based on index
            $statusIndex = $index % count($submissionStatuses);
            $status = $submissionStatuses[$statusIndex];

            // Calculate score and grade for graded submissions
            $score = null;
            $finalGrade = null;
            if ($status === 'graded') {
                // Create a realistic grade distribution
                $scorePercentages = [
                    0.5, 0.55, 0.6, 0.65, 0.7, 0.75, 0.8, 0.85, 0.9, 0.95, 1.0
                ];
                $scorePercentage = $scorePercentages[array_rand($scorePercentages)];
                // Add some randomness for realism
                $scorePercentage = max(0, min(1, $scorePercentage + (mt_rand(-5, 5) / 100)));

                $score = round($activity->total_points * $scorePercentage);
                $finalGrade = ($score / $activity->total_points) * 100;
            }

            // Get submission content based on activity format or default to empty
            $content = null;
            if ($status === 'submitted' || $status === 'graded') {
                $content = $contents[$activity->format] ?? 'Completed submission for this activity.';
            }

            // Get feedback for graded submissions
            $feedback = $status === 'graded' ? $this->getRealisticFeedback($score, $activity->total_points) : null;

            // Create submission record
            ActivitySubmission::firstOrCreate(
                [
                    'activity_id' => $activity->id,
                    'student_id' => $student->id,
                ],
                [
                    'content' => $content,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $feedback,
                    'submitted_at' => in_array($status, ['submitted', 'graded'])
                        ? now()->subDays(rand(1, 7))->subHours(rand(1, 23))
                        : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded'
                        ? now()->subDays(rand(0, 2))->subHours(rand(1, 23))
                        : null,
                ]
            );
        }
    }

    /**
     * Get a list of realistic student names
     *
     * @param int $offset Optional offset to get different names
     * @return array
     */
    private function getRealisticStudentNames(int $offset = 0): array
    {
        $names = [
            'Emma Johnson', 'Liam Smith', 'Olivia Williams', 'Noah Brown', 'Ava Jones',
            'Ethan Miller', 'Sophia Davis', 'Mason Garcia', 'Isabella Rodriguez', 'Logan Martinez',
            'Charlotte Wilson', 'Jacob Anderson', 'Mia Taylor', 'Jack Thomas', 'Amelia Hernandez',
            'Benjamin Moore', 'Harper Martin', 'Michael Jackson', 'Evelyn Thompson', 'Alexander White',
            'Abigail Harris', 'Daniel Clark', 'Emily Lewis', 'Matthew Lee', 'Elizabeth Walker',
            'Henry Hall', 'Sofia Allen', 'James Young', 'Avery King', 'Samuel Wright',
            'Scarlett Scott', 'Joseph Green', 'Victoria Baker', 'David Adams', 'Grace Nelson',
            'Carter Hill', 'Chloe Ramirez', 'Owen Campbell', 'Ella Mitchell', 'Wyatt Roberts',
            'Riley Carter', 'John Phillips', 'Lillian Evans', 'Gabriel Turner', 'Nora Torres',
            'Julian Collins', 'Zoey Parker', 'Luke Edwards', 'Hannah Morgan', 'Isaac Murphy'
        ];

        // Return 20 names starting from the offset
        return array_slice($names, $offset, 50);
    }

    /**
     * Generate student email from name
     *
     * @param string $name
     * @return string
     */
    private function generateStudentEmail(string $name): string
    {
        $nameParts = explode(' ', strtolower($name));
        $firstInitial = substr($nameParts[0], 0, 1);
        $lastName = end($nameParts);

        // Add some variation to emails
        $variations = ['', '01', '21', '22', '.edu'];
        $variation = $variations[array_rand($variations)];

        return $firstInitial . $lastName . $variation . '@student.edu';
    }

    /**
     * Get random student note
     *
     * @param int $index
     * @return string|null
     */
    private function getRandomStudentNote(int $index): ?string
    {
        // Only add notes to some students
        if ($index % 4 !== 0) {
            return null;
        }

        $notes = [
            'Transfer student from Springfield High',
            'Plays on the school basketball team',
            'Participates in debate club',
            'Has accommodations for extended time on tests',
            'Excels in mathematics and science subjects',
            'International exchange student',
            'Student council representative',
            'Requires seating at the front of class (vision)',
            'Active in theater program',
            'Demonstrated leadership skills in group projects'
        ];

        return $notes[$index % count($notes)];
    }

    /**
     * Get realistic feedback based on score
     *
     * @param int|null $score
     * @param int $totalPoints
     * @return string
     */
    private function getRealisticFeedback(?int $score, int $totalPoints): string
    {
        if (!$score) {
            return 'Please see me during office hours to discuss this submission.';
        }

        $percentage = ($score / $totalPoints) * 100;

        if ($percentage >= 90) {
            $feedback = [
                "Excellent work! Your understanding of the concepts is clear and well-presented.",
                "Outstanding submission. You've demonstrated a thorough grasp of the material.",
                "Exceptional work. Your analysis shows depth and critical thinking.",
                "Very well done! Your submission exceeded expectations in both content and presentation."
            ];
        } elseif ($percentage >= 80) {
            $feedback = [
                "Good work overall. Your understanding of key concepts is solid.",
                "Strong submission with thoughtful analysis. A few minor areas could be developed further.",
                "Well-structured work showing good comprehension. Continue developing your analytical skills.",
                "Good job. You've addressed the main requirements effectively."
            ];
        } elseif ($percentage >= 70) {
            $feedback = [
                "Satisfactory work. You've covered the basics, but could explore some concepts in more depth.",
                "Adequate submission that meets requirements. More detailed analysis would strengthen your work.",
                "You've demonstrated basic understanding. Consider more specific examples in future work.",
                "Your work shows promise. Focus on developing more thorough explanations of key concepts."
            ];
        } else {
            $feedback = [
                "This submission needs improvement. Please review the course materials and consider revising.",
                "There are several areas that need attention. Let's discuss during office hours.",
                "Your work shows some understanding, but key concepts are missing or incorrect.",
                "This submission doesn't fully address the requirements. Please review the instructions carefully."
            ];
        }

        return $feedback[array_rand($feedback)];
    }
}

// Helper function to randomly select an item from an array
if (!function_exists('array_random')) {
    function array_random($array) {
        return $array[array_rand($array)];
    }
}
