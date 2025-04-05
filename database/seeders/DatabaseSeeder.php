<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\ActivitySubmission;
use App\Models\ActivityType;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $classroomWithInvitedStudents = $this->createClassroomShs($teacher);

        // Create second classroom with unlinked student records
        $classroomWithUnlinkedStudents = $this->createClassroomCollegeTerm(
            $teacher
        );

        // Set default team for teacher if not already set
        if (! $teacher->current_team_id) {
            $teacher->current_team_id = $classroomWithInvitedStudents->id;
            $teacher->save();
        }

        // Seed exams
        $this->call(ExamSeeder::class);
    }

    /**
     * Create teacher account
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
            [
                'name' => 'Quiz',
                'description' => 'Short assessment to test knowledge',
            ],
            [
                'name' => 'Assignment',
                'description' => 'Task to be completed by students',
            ],
            [
                'name' => 'Project',
                'description' => 'Larger task requiring planning and execution',
            ],
            [
                'name' => 'Presentation',
                'description' => 'Oral presentation of a topic',
            ],
            [
                'name' => 'Discussion',
                'description' => 'Group discussion on a topic',
            ],
            [
                'name' => 'Lab Work',
                'description' => 'Hands-on laboratory activity',
            ],
            [
                'name' => 'Essay',
                'description' => 'Written composition on a specific topic',
            ],
            [
                'name' => 'Exam',
                'description' => 'Formal assessment of knowledge',
            ],
        ];

        foreach ($activityTypes as $type) {
            ActivityType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }
    }

    /**
     * Create an SHS classroom with invited student users
     */
    private function createClassroomShs(User $teacher): Team
    {
        // Create or retrieve team with SHS config
        $classroom = Team::firstOrCreate(
            [
                'name' => 'Grade 11 - STEM A', // More SHS-like name
                'user_id' => $teacher->id,
            ],
            [
                'personal_team' => false,
                'id' => Str::uuid(),
                'join_code' => 'STEM11', // Custom join code
                // SHS Configuration
                'grading_system_type' => Team::GRADING_SYSTEM_SHS,
                'shs_ww_weight' => 30,
                'shs_pt_weight' => 50,
                'shs_qa_weight' => 20,
                // Nullify College fields
                'college_grading_scale' => null,
                'college_prelim_weight' => null,
                'college_midterm_weight' => null,
                'college_final_weight' => null,
            ]
        );

        // Create student accounts and link them (existing logic is fine)
        $studentUsersData = [];
        $studentNames = $this->getRealisticStudentNames();
        for ($i = 0; $i < 20; $i++) {
            $studentName = $studentNames[$i];
            $email = $this->generateStudentEmail($studentName);
            $studentUser = User::firstOrCreate(
                ['email' => $email],
                [
                    // Attributes to use IF CREATING the user
                    'name' => $studentName,
                    'password' => Hash::make('password'), // Need a default password
                    'email_verified_at' => now(), // Optionally mark as verified
                    'remember_token' => Str::random(10),
                    // Add any other required fields for your users table here
                ] /* ... user details ... */
            );
            if (! $studentUser->belongsToTeam($classroom)) {
                // Add the student user to the classroom team.
                // Assuming 'student' is the desired role key in the pivot table.
                // Adjust 'student' if your application uses a different role key.
                $classroom
                    ->users()
                    ->attach($studentUser, ['role' => 'student']);

                // Optionally, set the user's current team if this is their first team
                if (is_null($studentUser->current_team_id)) {
                    $studentUser
                        ->forceFill([
                            'current_team_id' => $classroom->id,
                        ])
                        ->save();
                }
            }

            // Create linked student record
            $student = Student::firstOrCreate(
                ['team_id' => $classroom->id, 'user_id' => $studentUser->id],
                [
                    'name' => $studentUser->name,
                    'email' => $studentUser->email,
                    'status' => 'active',
                    'student_id' => 'SHS'.
                        str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), // Use SHS prefix
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'birth_date' => now()
                        ->subYears(rand(16, 18))
                        ->subDays(rand(1, 365)), // SHS age range
                    'notes' => $this->getRandomStudentNote($i),
                ]
            );
            $studentUsersData[] = [
                'user' => $studentUser,
                'student' => $student,
            ];
        }

        // Create sample activities and submissions FOR SHS
        $this->createClassroomActivities(
            $classroom,
            $teacher,
            collect($studentUsersData)
        );

        return $classroom;
    }

    /**
     * Create a College (Term-Based) classroom with unlinked student records
     */
    private function createClassroomCollegeTerm(User $teacher): Team
    {
        // Create or retrieve team with College Term config
        $classroom = Team::firstOrCreate(
            [
                'name' => 'Introduction to Psychology', // More College-like name
                'user_id' => $teacher->id,
            ],
            [
                'personal_team' => false,
                'id' => Str::uuid(),
                'join_code' => 'PSY101', // Custom join code
                // College Term Configuration (Example: Term-Based, 5-Point Scale)
                'grading_system_type' => Team::GRADING_SYSTEM_COLLEGE,
                'college_grading_scale' => Team::COLLEGE_SCALE_TERM_5_POINT,
                'college_prelim_weight' => 30,
                'college_midterm_weight' => 30,
                'college_final_weight' => 40,
                // Nullify SHS fields
                'shs_ww_weight' => null,
                'shs_pt_weight' => null,
                'shs_qa_weight' => null,
            ]
        );

        // Create unlinked student records (existing logic is fine)
        $students = [];
        $studentNames = $this->getRealisticStudentNames(30); // Get different names
        for ($i = 0; $i < 20; $i++) {
            // ... (existing unlinked student creation logic) ...
            $studentName = $studentNames[$i];
            $email = $this->generateStudentEmail($studentName);
            $student = Student::firstOrCreate(
                ['team_id' => $classroom->id, 'email' => $email],
                [
                    'name' => $studentName,
                    'status' => 'active',
                    'user_id' => null,
                    'student_id' => 'COLL'.
                        str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), // Use COLL prefix
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'birth_date' => now()
                        ->subYears(rand(18, 22))
                        ->subDays(rand(1, 365)), // College age range
                    'notes' => $this->getRandomStudentNote($i),
                ]
            );
            $students[] = $student;
        }

        // Create sample activities and submissions FOR College Term
        $this->createClassroomActivities(
            $classroom,
            $teacher,
            collect($students)
        );

        return $classroom;
    }

    /**
     * Create activities and submissions for a classroom, adapting to the team's grading system.
     */
    private function createClassroomActivities(
        Team $classroom,
        User $teacher,
        $students
    ): void {
        // Get activity types
        $quizType = ActivityType::where('name', 'Quiz')->first();
        $assignmentType = ActivityType::where('name', 'Assignment')->first();
        $projectType = ActivityType::where('name', 'Project')->first();
        $essayType = ActivityType::where('name', 'Essay')->first();
        $presentationType = ActivityType::where(
            'name',
            'Presentation'
        )->first();
        $quarterlyExamType = ActivityType::where(
            'name',
            'Quarterly Exam'
        )->first(); // For SHS QA

        // --- Create activities based on classroom type ---
        // Simple distribution strategy:
        // Activity 1: Quiz (WW / Prelim)
        // Activity 2: Essay (WW / Prelim)
        // Activity 3: Presentation (PT / Midterm)
        // Activity 4: Assignment (WW / Midterm)
        // Activity 5: Project (PT / Final)
        // Activity 6: Quarterly Exam (QA / Final) - only for SHS

        $this->createActivity(
            $classroom,
            $teacher,
            $students,
            $quizType,
            title: 'Unit 1 Quiz',
            description: 'Quiz covering the first unit.',
            instructions: 'Answer all questions.',
            category: 'written',
            component: Activity::COMPONENT_WRITTEN_WORK,
            term: Activity::TERM_PRELIM,
            totalPoints: 25,
            creditUnits: 1.0 // Assign units for potential GWA use later
        );

        $this->createActivity(
            $classroom,
            $teacher,
            $students,
            $essayType,
            title: 'Introductory Essay',
            description: 'Essay on core concepts.',
            instructions: 'Write a 500-word essay.',
            category: 'written',
            component: Activity::COMPONENT_WRITTEN_WORK,
            term: Activity::TERM_PRELIM,
            totalPoints: 50,
            creditUnits: 1.5
        );

        $this->createActivity(
            $classroom,
            $teacher,
            $students,
            $presentationType,
            title: 'Topic Presentation',
            description: 'Present a chosen topic.',
            instructions: '5-minute presentation with slides.',
            category: 'performance',
            component: Activity::COMPONENT_PERFORMANCE_TASK,
            term: Activity::TERM_MIDTERM,
            totalPoints: 50,
            creditUnits: 2.0
        );

        $this->createActivity(
            $classroom,
            $teacher,
            $students,
            $assignmentType,
            title: 'Mid-Unit Assignment',
            description: 'Practice exercises.',
            instructions: 'Complete the worksheet.',
            category: 'written',
            component: Activity::COMPONENT_WRITTEN_WORK,
            term: Activity::TERM_MIDTERM,
            totalPoints: 30,
            creditUnits: 1.0
        );

        $this->createActivity(
            $classroom,
            $teacher,
            $students,
            $projectType,
            title: 'Group Research Project',
            description: 'Collaborative research.',
            instructions: 'Work in groups. Submit paper & presentation.',
            category: 'performance', // Often graded on output/presentation
            component: Activity::COMPONENT_PERFORMANCE_TASK, // Primary component is often PT for projects
            term: Activity::TERM_FINAL,
            totalPoints: 100,
            creditUnits: 3.0,
            isGroup: true
        );

        // Add Quarterly Exam only for SHS
        if ($classroom->usesShsGrading() && $quarterlyExamType) {
            $this->createActivity(
                $classroom,
                $teacher,
                $students,
                $quarterlyExamType,
                title: 'Quarterly Examination',
                description: 'Comprehensive assessment for the quarter.',
                instructions: 'Answer all sections.',
                category: 'written',
                component: Activity::COMPONENT_QUARTERLY_ASSESSMENT,
                term: null, // QA is SHS specific
                totalPoints: 100,
                creditUnits: null // No units for SHS QA
            );
        }
        // Add a Final Exam for College Term-Based (could also use quarterlyExamType if desired)
        elseif ($classroom->usesCollegeTermGrading() && $quarterlyExamType) {
            $this->createActivity(
                $classroom,
                $teacher,
                $students,
                $quarterlyExamType, // Reuse type or create 'Final Exam' type
                title: 'Final Examination',
                description: 'Comprehensive assessment for the course.',
                instructions: 'Answer all sections.',
                category: 'written',
                component: null,
                term: Activity::TERM_FINAL,
                totalPoints: 100,
                creditUnits: null // Often weighted by term, not direct units
            );
        }
    }

    /**
     * Helper to create a single activity and its submissions, adapting to grading system.
     */
    private function createActivity(
        Team $classroom,
        User $teacher, // Teacher object is passed in
        $students,
        ?ActivityType $activityType,
        string $title,
        string $description,
        string $instructions,
        string $category,
        ?string $component,
        ?string $term,
        int $totalPoints,
        ?float $creditUnits,
        bool $isGroup = false
    ): void {
        if (! $activityType) {
            return;
        }

        $activityData = [
            // Common fields
            'teacher_id' => $teacher->id, // Assuming created_by implies teacher
            'team_id' => $classroom->id,
            'activity_type_id' => $activityType->id,
            'description' => $description,
            'instructions' => $instructions,
            'category' => $category,
            'total_points' => $totalPoints,
            'status' => 'published',
            'mode' => $isGroup ? 'group' : 'individual',
            // System Specific Fields
            'component_type' => $classroom->usesShsGrading()
                ? $component
                : null,
            'term' => $classroom->usesCollegeTermGrading() ? $term : null,
            'credit_units' => $classroom->usesCollegeGwaGrading()
                ? $creditUnits
                : null, // Assign only if GWA system
        ];

        // Use firstOrCreate with a unique constraint (team_id, title)
        $activity = Activity::firstOrCreate(
            [
                'team_id' => $classroom->id,
                'title' => $title,
            ],
            $activityData
        );

        // Add roles if it's a group project (Simplified - assumes project type means group)
        if ($isGroup) {
            $roles = ['Leader', 'Researcher', 'Writer', 'Presenter'];
            foreach ($roles as $roleName) {
                ActivityRole::firstOrCreate(
                    ['activity_id' => $activity->id, 'name' => $roleName],
                    ['description' => 'Role for the '.$title]
                );
            }
            // Note: Group assignment logic is complex and not fully implemented here.
            // Seeding submissions for group projects might need refinement later.
        }

        // Create submissions (handles user/student pairs and student objects)
        $this->createActivitySubmissions($activity, $students, $teacher);
    }

    /**
     * Create an essay activity with deadline
     */
    private function createEssayActivity(
        Team $classroom,
        User $teacher,
        $students,
        $essayType
    ): void {
        if (! $essayType) {
            return;
        }

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
    private function createAssignmentActivity(
        Team $classroom,
        User $teacher,
        $students,
        $assignmentType
    ): void {
        if (! $assignmentType) {
            return;
        }

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
     * (This method remains largely the same, grading system logic is handled during calculation)
     */
    private function createActivitySubmissions(
        $activity,
        $students,
        $teacher
    ): void {
        $submissionStatuses = [
            'not_started',
            'in_progress',
            'submitted',
            'graded',
        ];
        $contents = [
            /* ... existing contents ... */
        ];

        foreach ($students as $index => $studentData) {
            $student = isset($studentData['student'])
                ? $studentData['student']
                : $studentData;
            $statusIndex = $index % count($submissionStatuses);
            $status = $submissionStatuses[$statusIndex];
            $score = null;
            // $finalGrade = null; // We no longer store final_grade on submission

            if ($status === 'graded' && $activity->total_points > 0) {
                // Realistic grade distribution
                $scorePercentages = [
                    0.5,
                    0.55,
                    0.6,
                    0.65,
                    0.7,
                    0.75,
                    0.8,
                    0.85,
                    0.9,
                    0.95,
                    1.0,
                ];
                $scorePercentage =
                    $scorePercentages[array_rand($scorePercentages)];
                $scorePercentage = max(
                    0,
                    min(1, $scorePercentage + mt_rand(-5, 5) / 100)
                );
                $score = round($activity->total_points * $scorePercentage);
            } elseif ($status === 'graded') {
                $score = 0; // Assign 0 if graded but total_points is 0 or less
            }

            $content = null;
            if ($status === 'submitted' || $status === 'graded') {
                $content =
                    $contents[$activity->format ?? 'assignment'] ??
                    'Completed submission.'; // Use format if available
            }
            $feedback =
                $status === 'graded'
                    ? $this->getRealisticFeedback(
                        $score,
                        $activity->total_points
                    )
                    : null;

            ActivitySubmission::updateOrCreate(
                // Use updateOrCreate to avoid duplicates on re-seed
                [
                    'activity_id' => $activity->id,
                    'student_id' => $student->id,
                ],
                [
                    'content' => $content,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => null, // Ensure final_grade is always null
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
     * @param  int  $offset  Optional offset to get different names
     */
    private function getRealisticStudentNames(int $offset = 0): array
    {
        $names = [
            'Emma Johnson',
            'Liam Smith',
            'Olivia Williams',
            'Noah Brown',
            'Ava Jones',
            'Ethan Miller',
            'Sophia Davis',
            'Mason Garcia',
            'Isabella Rodriguez',
            'Logan Martinez',
            'Charlotte Wilson',
            'Jacob Anderson',
            'Mia Taylor',
            'Jack Thomas',
            'Amelia Hernandez',
            'Benjamin Moore',
            'Harper Martin',
            'Michael Jackson',
            'Evelyn Thompson',
            'Alexander White',
            'Abigail Harris',
            'Daniel Clark',
            'Emily Lewis',
            'Matthew Lee',
            'Elizabeth Walker',
            'Henry Hall',
            'Sofia Allen',
            'James Young',
            'Avery King',
            'Samuel Wright',
            'Scarlett Scott',
            'Joseph Green',
            'Victoria Baker',
            'David Adams',
            'Grace Nelson',
            'Carter Hill',
            'Chloe Ramirez',
            'Owen Campbell',
            'Ella Mitchell',
            'Wyatt Roberts',
            'Riley Carter',
            'John Phillips',
            'Lillian Evans',
            'Gabriel Turner',
            'Nora Torres',
            'Julian Collins',
            'Zoey Parker',
            'Luke Edwards',
            'Hannah Morgan',
            'Isaac Murphy',
        ];

        // Return 20 names starting from the offset
        return array_slice($names, $offset, 50);
    }

    /**
     * Generate student email from name
     */
    private function generateStudentEmail(string $name): string
    {
        $nameParts = explode(' ', strtolower($name));
        $firstInitial = substr($nameParts[0], 0, 1);
        $lastName = end($nameParts);

        // Add some variation to emails
        $variations = ['', '01', '21', '22', '.edu'];
        $variation = $variations[array_rand($variations)];

        return $firstInitial.$lastName.$variation.'@student.edu';
    }

    /**
     * Get random student note
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
            'Demonstrated leadership skills in group projects',
        ];

        return $notes[$index % count($notes)];
    }

    /**
     * Get realistic feedback based on score
     */
    private function getRealisticFeedback(?int $score, int $totalPoints): string
    {
        if (! $score) {
            return 'Please see me during office hours to discuss this submission.';
        }

        $percentage = ($score / $totalPoints) * 100;

        if ($percentage >= 90) {
            $feedback = [
                'Excellent work! Your understanding of the concepts is clear and well-presented.',
                "Outstanding submission. You've demonstrated a thorough grasp of the material.",
                'Exceptional work. Your analysis shows depth and critical thinking.',
                'Very well done! Your submission exceeded expectations in both content and presentation.',
            ];
        } elseif ($percentage >= 80) {
            $feedback = [
                'Good work overall. Your understanding of key concepts is solid.',
                'Strong submission with thoughtful analysis. A few minor areas could be developed further.',
                'Well-structured work showing good comprehension. Continue developing your analytical skills.',
                "Good job. You've addressed the main requirements effectively.",
            ];
        } elseif ($percentage >= 70) {
            $feedback = [
                "Satisfactory work. You've covered the basics, but could explore some concepts in more depth.",
                'Adequate submission that meets requirements. More detailed analysis would strengthen your work.',
                "You've demonstrated basic understanding. Consider more specific examples in future work.",
                'Your work shows promise. Focus on developing more thorough explanations of key concepts.',
            ];
        } else {
            $feedback = [
                'This submission needs improvement. Please review the course materials and consider revising.',
                "There are several areas that need attention. Let's discuss during office hours.",
                'Your work shows some understanding, but key concepts are missing or incorrect.',
                "This submission doesn't fully address the requirements. Please review the instructions carefully.",
            ];
        }

        return $feedback[array_rand($feedback)];
    }
}

// Helper function to randomly select an item from an array
if (! function_exists('array_random')) {
    function array_random($array)
    {
        return $array[array_rand($array)];
    }
}
