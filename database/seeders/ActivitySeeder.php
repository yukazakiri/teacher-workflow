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
        $students = $team->students()->get(); // Use relationship

        if ($students->isEmpty()) {
            $this->command->info("No students found in team {$team->name}. Skipping activity creation.");
            return;
        }

        // Get all activity types
        $activityTypes = ActivityType::all()->keyBy('name'); // Key by name for easier lookup

        if ($activityTypes->isEmpty()) {
            $this->command->info('No activity types found. Please run DatabaseSeeder first.');
            return;
        }

        // 1. Create a quiz with multiple-choice questions (WW / Prelim)
        $this->createMultipleChoiceQuiz($team, $teacher, $students, $activityTypes->get('Quiz'));

        // 2. Create a group project with specific roles and groups (PT / Final)
        $this->createGroupProject($team, $teacher, $students, $activityTypes->get('Project'));

        // 3. Create a take-home essay with a deadline (WW / Midterm)
        $this->createTakeHomeEssay($team, $teacher, $students, $activityTypes->get('Essay'));

        // 4. Create a performance-based individual presentation (PT / Midterm)
        $this->createIndividualPresentation($team, $teacher, $students, $activityTypes->get('Presentation'));

        // 5. Create a lab work activity (PT / Prelim) - Often performance-based
        $this->createLabWork($team, $teacher, $students, $activityTypes->get('Lab Work'));

        // 6. Create a discussion activity (WW / Prelim) - Often participation-based
        $this->createDiscussionActivity($team, $teacher, $students, $activityTypes->get('Discussion'));

        // 7. Create an exam (QA / Final)
        $this->createExamActivity($team, $teacher, $students, $activityTypes->get('Exam'));
    }

    /**
     * Create a multiple-choice quiz (Assign to WW / Prelim)
     */
    private function createMultipleChoiceQuiz(Team $team, User $teacher, $students, ?ActivityType $quizType): void
    {
        if (!$quizType) return;

        $activityData = [
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
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_WRITTEN_WORK : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_PRELIM : null,
            'credit_units' => $team->usesCollegeGwaGrading() ? 1.0 : null,
        ];

        $quiz = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Multiple Choice Science Quiz',
            ],
            $activityData
        );

        // Simplified submissions
        foreach ($students->take(15) as $index => $student) {
            $status = match ($index % 3) { // Simplified states
                0 => 'not_started',
                1 => 'submitted',
                2 => 'graded',
            };
            $score = $status === 'graded' ? rand(0, $quiz->total_points) : null;

            ActivitySubmission::updateOrCreate(
                ['activity_id' => $quiz->id, 'student_id' => $student->id],
                [
                    'content' => $status !== 'not_started' ? 'Completed quiz.' : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => null, // Always null now
                    'feedback' => null, // Removed feedback
                    'submitted_at' => $status !== 'not_started' ? now()->subHours(rand(1, 48)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 24)) : null,
                ]
            );
        }
    }

    /**
     * Create a group project (Assign to PT / Final)
     */
    private function createGroupProject(Team $team, User $teacher, $students, ?ActivityType $projectType): void
    {
        if (!$projectType) return;

        $activityData = [
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
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_PERFORMANCE_TASK : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_FINAL : null,
            'credit_units' => $team->usesCollegeGwaGrading() ? 3.0 : null,
        ];

        $project = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Environmental Science Project',
            ],
            $activityData
        );

        // Create roles for the project
        $rolesConfig = [
            ['name' => 'Research Coordinator', 'description' => 'Coordinates research efforts and ensures quality sources'],
            ['name' => 'Data Analyst', 'description' => 'Analyzes data and creates visualizations'],
            ['name' => 'Solution Designer', 'description' => 'Develops practical solutions to the environmental issue'],
            ['name' => 'Presentation Lead', 'description' => 'Creates and delivers the final presentation'],
        ];
        $activityRoles = [];
        foreach ($rolesConfig as $roleConfig) {
            $role = ActivityRole::firstOrCreate(
                ['activity_id' => $project->id, 'name' => $roleConfig['name']],
                ['description' => $roleConfig['description']]
            );
            $activityRoles[] = $role;
        }

        // Create groups and assign students/submissions (Simplified)
        $groupCount = 4;
        $studentsPerGroup = ceil($students->count() / $groupCount);
        $studentChunks = $students->chunk($studentsPerGroup);

        foreach ($studentChunks as $index => $groupStudents) {
            if ($index >= $groupCount) break; // Ensure we don't create more groups than planned
            $groupIndex = $index + 1; // 1-based index for naming

            $group = ActivityGroup::firstOrCreate(
                ['activity_id' => $project->id, 'name' => "Group {$groupIndex}"],
                ['description' => "Environmental research group {$groupIndex}"]
            );

            // Simplified group submission state
            $groupStatus = match ($index % 3) {
                0 => 'in_progress',
                1 => 'submitted',
                2 => 'graded',
            };
            $groupScore = $groupStatus === 'graded' ? rand(100, $project->total_points) : null;

            $groupSubmission = ActivitySubmission::updateOrCreate(
                ['activity_id' => $project->id, 'activity_group_id' => $group->id, 'student_id' => null], // Group submission has no student_id
                [
                    'content' => "Group {$groupIndex} project submission.",
                    'status' => $groupStatus,
                    'score' => $groupScore,
                    'final_grade' => null,
                    'submitted_at' => $groupStatus !== 'in_progress' ? now()->subDays(rand(1, 5)) : null,
                    'graded_by' => $groupStatus === 'graded' ? $teacher->id : null,
                    'graded_at' => $groupStatus === 'graded' ? now()->subDays(rand(1, 3)) : null,
                ]
            );

            // Assign students to the group with roles and create linked individual submissions
            foreach ($groupStudents as $studentIndex => $student) {
                $role = $activityRoles[$studentIndex % count($activityRoles)];

                ActivitySubmission::updateOrCreate(
                    ['activity_id' => $project->id, 'student_id' => $student->id],
                    [
                        'activity_group_id' => $group->id, // Link student submission to group
                        'content' => "Contribution as {$role->name}.",
                        'status' => $groupSubmission->status, // Inherit status from group
                        'score' => $groupSubmission->score, // Inherit score from group (can be overridden later)
                        'final_grade' => null,
                        'submitted_at' => $groupSubmission->submitted_at,
                        'graded_by' => $groupSubmission->graded_by,
                        'graded_at' => $groupSubmission->graded_at,
                    ]
                );
            }
        }
    }

    /**
     * Create a take-home essay (Assign to WW / Midterm)
     */
    private function createTakeHomeEssay(Team $team, User $teacher, $students, ?ActivityType $essayType): void
    {
        if (!$essayType) return;

        $activityData = [
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
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_WRITTEN_WORK : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_MIDTERM : null,
            'credit_units' => $team->usesCollegeGwaGrading() ? 1.5 : null,
        ];

        $essay = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Literary Analysis Essay',
            ],
            $activityData
        );

        // Simplified submissions
        foreach ($students as $index => $student) {
            $status = match ($index % 3) {
                0 => 'not_started',
                1 => 'submitted',
                2 => 'graded',
            };
            $score = $status === 'graded' ? rand(60, $essay->total_points) : null;

            ActivitySubmission::updateOrCreate(
                ['activity_id' => $essay->id, 'student_id' => $student->id],
                [
                    'content' => $status !== 'not_started' ? 'Submitted essay.' : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => null,
                    'feedback' => null,
                    'submitted_at' => $status !== 'not_started' ? now()->subHours(rand(1, 72)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subHours(rand(1, 48)) : null,
                ]
            );
        }
    }

    /**
     * Create an individual presentation (Assign to PT / Midterm)
     */
    private function createIndividualPresentation(Team $team, User $teacher, $students, ?ActivityType $presentationType): void
    {
        if (!$presentationType) return;

        $activityData = [
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
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_PERFORMANCE_TASK : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_MIDTERM : null,
            'credit_units' => $team->usesCollegeGwaGrading() ? 2.0 : null,
        ];

        $presentation = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Historical Figure Presentation',
            ],
            $activityData
        );

        // Simplified submissions
        foreach ($students->take(12) as $index => $student) {
             $status = match ($index % 3) {
                0 => 'not_started',
                1 => 'submitted',
                2 => 'graded',
            };
            $score = $status === 'graded' ? rand(30, $presentation->total_points) : null;
            $historicalFigures = ['Marie Curie', 'Albert Einstein', 'Ada Lovelace', 'Nikola Tesla'];
            $figure = $historicalFigures[$index % count($historicalFigures)];

            ActivitySubmission::updateOrCreate(
                ['activity_id' => $presentation->id, 'student_id' => $student->id],
                [
                    'content' => $status !== 'not_started' ? "Presentation on {$figure}." : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => null,
                    'feedback' => null,
                    'submitted_at' => $status !== 'not_started' ? now()->subDays(rand(1, 7)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subDays(rand(1, 5)) : null,
                ]
            );
        }
    }

    /**
     * Create a lab work activity (Assign to PT / Prelim)
     */
    private function createLabWork(Team $team, User $teacher, $students, ?ActivityType $labType): void
    {
        if (!$labType) return;

        $activityData = [
            'teacher_id' => $teacher->id,
            'team_id' => $team->id,
            'activity_type_id' => $labType->id,
            'title' => 'Biology Lab: Cell Structure',
            'description' => 'Observe and identify cell structures under a microscope',
            'instructions' => 'Follow the lab procedure to prepare slides, observe different cell types, and draw and label what you see.',
            'format' => 'project', // Lab reports often fit project format
            'category' => 'performance', // Doing the lab is performance
            'mode' => 'individual',
            'total_points' => 40,
            'status' => 'published',
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_PERFORMANCE_TASK : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_PRELIM : null,
            'credit_units' => $team->usesCollegeGwaGrading() ? 1.5 : null,
        ];

        $lab = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Biology Lab: Cell Structure',
            ],
            $activityData
        );

        // Simplified submissions
        foreach ($students->take(18) as $index => $student) {
            $status = match ($index % 3) {
                0 => 'not_started',
                1 => 'submitted',
                2 => 'graded',
            };
            $score = $status === 'graded' ? rand(25, $lab->total_points) : null;

            ActivitySubmission::updateOrCreate(
                ['activity_id' => $lab->id, 'student_id' => $student->id],
                [
                    'content' => $status !== 'not_started' ? "Completed lab report." : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => null,
                    'feedback' => null,
                    'submitted_at' => $status !== 'not_started' ? now()->subDays(rand(1, 10)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subDays(rand(1, 7)) : null,
                ]
            );
        }
    }

    /**
     * Create a discussion activity (Assign to WW / Prelim) - Draft Status
     */
    private function createDiscussionActivity(Team $team, User $teacher, $students, ?ActivityType $discussionType): void
    {
        if (!$discussionType) return;

        $activityData = [
            'teacher_id' => $teacher->id,
            'team_id' => $team->id,
            'activity_type_id' => $discussionType->id,
            'title' => 'Current Events Discussion',
            'description' => 'Group discussion on recent news events',
            'instructions' => 'Each group will be assigned a current event to discuss. Prepare by researching the topic and be ready to discuss its implications.',
            'format' => 'discussion',
            'category' => 'written', // Often graded on participation/written summary
            'mode' => 'group',
            'total_points' => 30,
            'status' => 'draft', // Keep as draft
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_WRITTEN_WORK : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_PRELIM : null,
            'credit_units' => $team->usesCollegeGwaGrading() ? 0.5 : null,
        ];

        $discussion = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Current Events Discussion',
            ],
            $activityData
        );

        // Create roles (even for draft)
        $rolesConfig = [
            ['name' => 'Discussion Leader', 'description' => 'Facilitates the discussion'],
            ['name' => 'Fact Checker', 'description' => 'Verifies facts'],
            ['name' => 'Devil\'s Advocate', 'description' => 'Presents alternative viewpoints'],
            ['name' => 'Summarizer', 'description' => 'Summarizes key points'],
        ];
        foreach ($rolesConfig as $roleConfig) {
            ActivityRole::firstOrCreate(
                ['activity_id' => $discussion->id, 'name' => $roleConfig['name']],
                ['description' => $roleConfig['description']]
            );
        }

        // No submissions for draft activities
    }

    /**
     * Create an exam activity (Assign to QA / Final)
     */
    private function createExamActivity(Team $team, User $teacher, $students, ?ActivityType $examType): void
    {
        if (!$examType) return;

        $activityData = [
            'teacher_id' => $teacher->id,
            'team_id' => $team->id,
            'activity_type_id' => $examType->id,
            'title' => 'Midterm Exam', // Title could vary, e.g., Final Exam
            'description' => 'Comprehensive exam covering course material',
            'instructions' => 'Complete all sections of the exam. You have 90 minutes.',
            'format' => 'quiz', // Exam often uses quiz format
            'category' => 'written',
            'mode' => 'individual',
            'total_points' => 100,
            'status' => 'published',
            'component_type' => $team->usesShsGrading() ? Activity::COMPONENT_QUARTERLY_ASSESSMENT : null,
            'term' => $team->usesCollegeTermGrading() ? Activity::TERM_FINAL : null, // Exams often in final term
            'credit_units' => $team->usesCollegeGwaGrading() ? null : null, // Exams often weighted by term, not direct units
        ];

        $exam = Activity::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'team_id' => $team->id,
                'title' => 'Midterm Exam', // Ensure this aligns with the unique constraint check
            ],
            $activityData
        );

        // Simplified submissions
        foreach ($students->take(15) as $index => $student) {
            $status = match ($index % 3) {
                0 => 'not_started',
                1 => 'submitted',
                2 => 'graded',
            };
            $score = $status === 'graded' ? rand(50, $exam->total_points) : null;

            ActivitySubmission::updateOrCreate(
                ['activity_id' => $exam->id, 'student_id' => $student->id],
                [
                    'content' => $status !== 'not_started' ? 'Completed exam.' : null,
                    'status' => $status,
                    'score' => $score,
                    'final_grade' => null,
                    'feedback' => null,
                    'submitted_at' => $status !== 'not_started' ? now()->subDays(14)->addMinutes(rand(60, 90)) : null,
                    'graded_by' => $status === 'graded' ? $teacher->id : null,
                    'graded_at' => $status === 'graded' ? now()->subDays(12) : null,
                ]
            );
        }
    }
}
