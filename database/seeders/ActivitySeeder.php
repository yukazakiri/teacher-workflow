<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\ActivityType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first teacher user and their team
        $teacher = User::first();

        if (!$teacher) {
            $this->command->info('Teacher user not found. Please run DatabaseSeeder first.');
            return;
        }

        $team = $teacher->currentTeam;

        if (!$team) {
            $this->command->info('Teacher team not found. Please run DatabaseSeeder first.');
            return;
        }

        // Get all students in the team
        $students = $team->users()->where('role', 'student')->get();

        if ($students->isEmpty()) {
            $this->command->info('No students found in the team. Please run DatabaseSeeder first.');
            return;
        }

        // Get all activity types
        $activityTypes = ActivityType::all();

        if ($activityTypes->isEmpty()) {
            $this->command->info('No activity types found. Please run DatabaseSeeder first.');
            return;
        }

        // Create additional activities for specific scenarios

        // 1. Create a quiz with multiple-choice questions
        $quizType = ActivityType::where('name', 'Quiz')->first();
        if ($quizType) {
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
            foreach ($students->take(10) as $index => $student) {
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

                    $quiz->submissions()->create([
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' || $status === 'graded' ? 'Completed quiz with multiple-choice answers.' : null,
                        'status' => $status,
                        'score' => $score,
                    ]);
                }
            }
        }

        // 2. Create a group project with specific roles and groups
        $projectType = ActivityType::where('name', 'Project')->first();
        if ($projectType) {
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
        }

        // 3. Create a take-home essay with a deadline
        $essayType = ActivityType::where('name', 'Essay')->first();
        if ($essayType) {
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
            foreach ($students->take(8) as $index => $student) {
                $submissionExists = $essay->submissions()
                    ->where('student_id', $student->id)
                    ->exists();

                if (!$submissionExists) {
                    $status = match ($index % 3) {
                        0 => 'not_started',
                        1 => 'in_progress',
                        2 => 'submitted',
                    };

                    $essay->submissions()->create([
                        'student_id' => $student->id,
                        'content' => $status === 'submitted' ? 'Submitted essay analyzing the protagonist\'s character development.' : null,
                        'status' => $status,
                    ]);
                }
            }
        }

        // 4. Create a performance-based individual presentation
        $presentationType = ActivityType::where('name', 'Presentation')->first();
        if ($presentationType) {
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
        }

        // 5. Create a lab work activity
        $labType = ActivityType::where('name', 'Lab Work')->first();
        if ($labType) {
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
        }

        // 6. Create a discussion activity
        $discussionType = ActivityType::where('name', 'Discussion')->first();
        if ($discussionType) {
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
        }

        // 7. Create an exam
        $examType = ActivityType::where('name', 'Exam')->first();
        if ($examType) {
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
            foreach ($students->take(15) as $student) {
                $submissionExists = $exam->submissions()
                    ->where('student_id', $student->id)
                    ->exists();

                if (!$submissionExists) {
                    $exam->submissions()->create([
                        'student_id' => $student->id,
                        'content' => 'Completed exam',
                        'status' => 'graded',
                        'score' => rand(50, 100),
                    ]);
                }
            }
        }
    }
}
