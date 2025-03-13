<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\ActivityRole;
use App\Models\ActivitySubmission;
use App\Models\ActivityType;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the test user
        $teacher = User::where('email', 'test@example.com')->first();

        if (!$teacher) {
            $this->command->info('Teacher user not found. Please run DatabaseSeeder first.');
            return;
        }

        // Get all teams for the teacher
        $teams = Team::where('user_id', $teacher->id)->get();

        if ($teams->isEmpty()) {
            $this->command->info('No teams found for the teacher. Please run DatabaseSeeder first.');
            return;
        }

        // Create activities for each team
        foreach ($teams as $team) {
            $this->createActivitiesForTeam($team, $teacher);
        }
    }

    /**
     * Create activities for a specific team
     */
    private function createActivitiesForTeam(Team $team, User $teacher): void
    {
        // Get all students in the team
        $students = Student::where('team_id', $team->id)->get();

        if ($students->isEmpty()) {
            $this->command->info("No students found in team {$team->name}. Skipping activity creation.");
            return;
        }

        // Get all activity types
        $activityTypes = ActivityType::all();

        if ($activityTypes->isEmpty()) {
            $this->command->info('No activity types found. Please run DatabaseSeeder first.');
            return;
        }

        // 1. Create a quiz with multiple-choice questions
        $this->createMultipleChoiceQuiz($team, $teacher, $students);

        // 2. Create a group project with specific roles and groups
        $this->createGroupProject($team, $teacher, $students);

        // 3. Create a take-home essay with a deadline
        $this->createTakeHomeEssay($team, $teacher, $students);

        // 4. Create a performance-based individual presentation
        $this->createIndividualPresentation($team, $teacher, $students);

        // 5. Create a lab work activity
        $this->createLabWork($team, $teacher, $students);

        // 6. Create a discussion activity
        $this->createDiscussionActivity($team, $teacher, $students);

        // 7. Create an exam
        $this->createExamActivity($team, $teacher, $students);
    }

    /**
     * Create a multiple-choice quiz
     */
    private function createMultipleChoiceQuiz(Team $team, User $teacher, $students): void
    {
        $quizType = ActivityType::where('name', 'Quiz')->first();
        if (!$quizType) return;

        $quiz = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Multiple Choice Science Quiz',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $quizType->id,
                'title' => 'Multiple Choice Science Quiz',
                'description' => 'A quiz with multiple-choice questions about basic science concepts',
                'instructions' => 'Select the best answer for each question. Each question is worth 2 points.',
                'format' => 'quiz',
                'category' => 'written',
                'mode' => 'individual',
                'total_points' => 20,
                'status' => 'published',
            ]
        );

        // Create submissions with different statuses
        foreach ($students->take(15) as $index => $student) {
            $submissionExists = $quiz->submissions()
                ->where('student_id', $student->id)
                ->exists();

            if (!$submissionExists) {
                $status = match ($index % 4) {
                    0 => 'not_started',
                    1 => 'in_progress',
                    2 => 'submitted',
                    3 => 'graded',
                };

                $score = $status === 'graded' ? rand(0, $quiz->total_points) : null;
                $finalGrade = $score !== null ? ($score / $quiz->total_points) * 100 : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                $quiz->submissions()->create([
                    'student_id' => $student->id,
                    'content' => $status === 'submitted' || $status === 'graded' ? 'Completed quiz with multiple-choice answers.' : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $feedback,
                    'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                ]);
            }
        }
    }

    /**
     * Create a group project with specific roles and groups
     */
    private function createGroupProject(Team $team, User $teacher, $students): void
    {
        $projectType = ActivityType::where('name', 'Project')->first();
        if (!$projectType) return;

        $project = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Environmental Science Project',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $projectType->id,
                'title' => 'Environmental Science Project',
                'description' => 'Research and present on an environmental issue affecting your local community',
                'instructions' => 'Work in groups to identify an environmental issue, research its causes and effects, and propose solutions.',
                'format' => 'project',
                'category' => 'performance',
                'mode' => 'group',
                'total_points' => 150,
                'status' => 'published',
            ]
        );

        // Create roles for the project
        $roles = [
            ['name' => 'Research Coordinator', 'description' => 'Coordinates research efforts and ensures quality sources'],
            ['name' => 'Data Analyst', 'description' => 'Analyzes data and creates visualizations'],
            ['name' => 'Solution Designer', 'description' => 'Develops practical solutions to the environmental issue'],
            ['name' => 'Presentation Lead', 'description' => 'Creates and delivers the final presentation'],
        ];

        $activityRoles = [];
        foreach ($roles as $roleConfig) {
            $role = ActivityRole::firstOrCreate(
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
            $activityRoles[] = $role;
        }

        // Create groups for the project (4 groups)
        $groupCount = 4;
        $groups = [];

        for ($i = 1; $i <= $groupCount; $i++) {
            $group = ActivityGroup::firstOrCreate(
                [
                    'activity_id' => $project->id,
                    'name' => "Group {$i}",
                ],
                [
                    'activity_id' => $project->id,
                    'name' => "Group {$i}",
                    'description' => "Environmental research group {$i}",
                ]
            );
            $groups[] = $group;
        }

        // Assign students to groups and roles
        $studentsPerGroup = ceil($students->count() / $groupCount);
        $studentChunks = $students->chunk($studentsPerGroup);

        foreach ($groups as $index => $group) {
            if (!isset($studentChunks[$index])) continue;

            $groupStudents = $studentChunks[$index];

            // Create a group submission
            $groupSubmission = ActivitySubmission::firstOrCreate(
                [
                    'activity_id' => $project->id,
                    'activity_group_id' => $group->id,
                ],
                [
                    'activity_id' => $project->id,
                    'activity_group_id' => $group->id,
                    'content' => "Group {$index + 1} project on local water pollution issues.",
                    'status' => $index % 3 === 0 ? 'in_progress' : ($index % 3 === 1 ? 'submitted' : 'graded'),
                    'score' => $index % 3 === 2 ? rand(100, 150) : null,
                    'final_grade' => $index % 3 === 2 ? rand(70, 98) : null,
                    'submitted_at' => $index % 3 !== 0 ? now()->subDays(rand(1, 5)) : null,
                    'graded_by' => $index % 3 === 2 ? $teacher->id : null,
                    'graded_at' => $index % 3 === 2 ? now()->subDays(rand(1, 3)) : null,
                ]
            );

            // Assign students to the group with roles
            foreach ($groupStudents as $studentIndex => $student) {
                // Assign a role to each student (cycling through available roles)
                $roleIndex = $studentIndex % count($activityRoles);
                $role = $activityRoles[$roleIndex];

                // Create individual submissions for each student in the group
                ActivitySubmission::firstOrCreate(
                    [
                        'activity_id' => $project->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'activity_id' => $project->id,
                        'student_id' => $student->id,
                        'activity_group_id' => $group->id,
                        'content' => "Contribution to group project as {$role->name}.",
                        'status' => $groupSubmission->status,
                        'score' => $groupSubmission->status === 'graded' ? rand(100, 150) : null,
                        'final_grade' => $groupSubmission->status === 'graded' ? rand(70, 98) : null,
                        'submitted_at' => $groupSubmission->submitted_at,
                        'graded_by' => $groupSubmission->graded_by,
                        'graded_at' => $groupSubmission->graded_at,
                    ]
                );
            }
        }
    }

    /**
     * Create a take-home essay with a deadline
     */
    private function createTakeHomeEssay(Team $team, User $teacher, $students): void
    {
        $essayType = ActivityType::where('name', 'Essay')->first();
        if (!$essayType) return;

        $essay = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Literary Analysis Essay',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $essayType->id,
                'title' => 'Literary Analysis Essay',
                'description' => 'Analyze a character from the novel we read in class',
                'instructions' => 'Write a 3-page essay analyzing a character from the novel, focusing on their development throughout the story.',
                'format' => 'assignment',
                'category' => 'written',
                'mode' => 'take_home',
                'total_points' => 100,
                'status' => 'published',
                'deadline' => now()->addDays(5),
            ]
        );

        // Create some submissions in different states
        foreach ($students as $index => $student) {
            $submissionExists = $essay->submissions()
                ->where('student_id', $student->id)
                ->exists();

            if (!$submissionExists) {
                $status = match ($index % 5) {
                    0 => 'not_started',
                    1, 2 => 'in_progress',
                    3 => 'submitted',
                    4 => 'graded',
                };

                $score = $status === 'graded' ? rand(60, 100) : null;
                $finalGrade = $score !== null ? $score : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                $essay->submissions()->create([
                    'student_id' => $student->id,
                    'content' => $status === 'submitted' || $status === 'graded' ?
                        'Submitted essay analyzing the protagonist\'s character development through the lens of their personal growth and relationships with other characters.' :
                        ($status === 'in_progress' ? 'Draft in progress - introduction and first body paragraph completed.' : null),
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $feedback,
                    'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subHours(rand(1, 72)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                ]);
            }
        }
    }

    /**
     * Create a performance-based individual presentation
     */
    private function createIndividualPresentation(Team $team, User $teacher, $students): void
    {
        $presentationType = ActivityType::where('name', 'Presentation')->first();
        if (!$presentationType) return;

        $presentation = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Historical Figure Presentation',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $presentationType->id,
                'title' => 'Historical Figure Presentation',
                'description' => 'Research and present on a significant historical figure',
                'instructions' => 'Prepare a 3-5 minute presentation about a historical figure of your choice, explaining their significance and impact.',
                'format' => 'presentation',
                'category' => 'performance',
                'mode' => 'individual',
                'total_points' => 50,
                'status' => 'published',
            ]
        );

        // Create submissions for some students
        foreach ($students->take(12) as $index => $student) {
            $submissionExists = $presentation->submissions()
                ->where('student_id', $student->id)
                ->exists();

            if (!$submissionExists) {
                $status = match ($index % 4) {
                    0 => 'not_started',
                    1 => 'in_progress',
                    2 => 'submitted',
                    3 => 'graded',
                };

                $historicalFigures = [
                    'Marie Curie', 'Albert Einstein', 'Mahatma Gandhi', 'Nelson Mandela',
                    'Ada Lovelace', 'Florence Nightingale', 'Leonardo da Vinci', 'Nikola Tesla',
                    'Rosa Parks', 'Martin Luther King Jr.', 'Frida Kahlo', 'Alan Turing'
                ];

                $figure = $historicalFigures[$index % count($historicalFigures)];
                $score = $status === 'graded' ? rand(30, 50) : null;
                $finalGrade = $score !== null ? ($score / $presentation->total_points) * 100 : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                $presentation->submissions()->create([
                    'student_id' => $student->id,
                    'content' => $status === 'submitted' || $status === 'graded' ?
                        "Presentation on {$figure} and their contributions to society." :
                        ($status === 'in_progress' ? "Preparing presentation on {$figure}." : null),
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $feedback,
                    'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subDays(rand(1, 7)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subDays(rand(1, 5)) : null,
                ]);
            }
        }
    }

    /**
     * Create a lab work activity
     */
    private function createLabWork(Team $team, User $teacher, $students): void
    {
        $labType = ActivityType::where('name', 'Lab Work')->first();
        if (!$labType) return;

        $lab = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Biology Lab: Cell Structure',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $labType->id,
                'title' => 'Biology Lab: Cell Structure',
                'description' => 'Observe and identify cell structures under a microscope',
                'instructions' => 'Follow the lab procedure to prepare slides, observe different cell types, and draw and label what you see.',
                'format' => 'project',
                'category' => 'performance',
                'mode' => 'individual',
                'total_points' => 40,
                'status' => 'published',
            ]
        );

        // Create submissions for some students
        foreach ($students->take(18) as $index => $student) {
            $submissionExists = $lab->submissions()
                ->where('student_id', $student->id)
                ->exists();

            if (!$submissionExists) {
                $status = match ($index % 4) {
                    0 => 'not_started',
                    1 => 'in_progress',
                    2 => 'submitted',
                    3 => 'graded',
                };

                $score = $status === 'graded' ? rand(25, 40) : null;
                $finalGrade = $score !== null ? ($score / $lab->total_points) * 100 : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                $lab->submissions()->create([
                    'student_id' => $student->id,
                    'content' => $status === 'submitted' || $status === 'graded' ?
                        "Completed lab report with observations of plant and animal cell structures, including detailed drawings and labels." :
                        ($status === 'in_progress' ? "Lab in progress - slides prepared, observations being recorded." : null),
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $feedback,
                    'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subDays(rand(1, 10)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subDays(rand(1, 7)) : null,
                ]);
            }
        }
    }

    /**
     * Create a discussion activity
     */
    private function createDiscussionActivity(Team $team, User $teacher, $students): void
    {
        $discussionType = ActivityType::where('name', 'Discussion')->first();
        if (!$discussionType) return;

        $discussion = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Current Events Discussion',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $discussionType->id,
                'title' => 'Current Events Discussion',
                'description' => 'Group discussion on recent news events',
                'instructions' => 'Each group will be assigned a current event to discuss. Prepare by researching the topic and be ready to discuss its implications.',
                'format' => 'discussion',
                'category' => 'written',
                'mode' => 'group',
                'total_points' => 30,
                'status' => 'draft',
            ]
        );

        // Create roles for the discussion
        $roles = [
            ['name' => 'Discussion Leader', 'description' => 'Facilitates the discussion and ensures all members participate'],
            ['name' => 'Fact Checker', 'description' => 'Verifies facts and provides additional context when needed'],
            ['name' => 'Devil\'s Advocate', 'description' => 'Presents alternative viewpoints to encourage critical thinking'],
            ['name' => 'Summarizer', 'description' => 'Takes notes and summarizes key points from the discussion'],
        ];

        foreach ($roles as $roleConfig) {
            ActivityRole::firstOrCreate(
                [
                    'activity_id' => $discussion->id,
                    'name' => $roleConfig['name'],
                ],
                [
                    'activity_id' => $discussion->id,
                    'name' => $roleConfig['name'],
                    'description' => $roleConfig['description'],
                ]
            );
        }

        // Since this activity is in draft status, we won't create submissions
    }

    /**
     * Create an exam activity
     */
    private function createExamActivity(Team $team, User $teacher, $students): void
    {
        $examType = ActivityType::where('name', 'Exam')->first();
        if (!$examType) return;

        $exam = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Midterm Exam',
            ],
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'activity_type_id' => $examType->id,
                'title' => 'Midterm Exam',
                'description' => 'Comprehensive exam covering all material from the first half of the course',
                'instructions' => 'Complete all sections of the exam. You have 90 minutes to finish.',
                'format' => 'quiz',
                'category' => 'written',
                'mode' => 'individual',
                'total_points' => 100,
                'status' => 'published',
            ]
        );

        // Create graded submissions for some students
        foreach ($students->take(15) as $index => $student) {
            $submissionExists = $exam->submissions()
                ->where('student_id', $student->id)
                ->exists();

            if (!$submissionExists) {
                // For exams, most students have completed and been graded
                $status = $index < 12 ? 'graded' : ($index < 14 ? 'submitted' : 'not_started');

                $score = $status === 'graded' ? rand(50, 100) : null;
                $finalGrade = $score !== null ? $score : null;
                $feedback = $status === 'graded' ? $this->getRandomFeedback() : null;

                $exam->submissions()->create([
                    'student_id' => $student->id,
                    'content' => $status === 'submitted' || $status === 'graded' ? 'Completed exam with all sections answered.' : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $feedback,
                    'submitted_at' => $status === 'submitted' || $status === 'graded' ? now()->subDays(14)->addMinutes(rand(60, 90)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subDays(12) : null,
                ]);
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
