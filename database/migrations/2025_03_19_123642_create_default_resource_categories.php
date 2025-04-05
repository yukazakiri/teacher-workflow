<?php

use App\Models\ResourceCategory;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Default categories for all existing teams
        $teams = Team::all();

        foreach ($teams as $team) {
            $this->createDefaultCategoriesForTeam($team->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove default categories by their names
        $defaultTeacherCategories = ['Lesson Plans', 'Answer Keys', 'Worksheets', 'Quiz Templates', 'Exams'];
        $defaultStudentCategories = ['Handouts', 'Reading Materials', 'Homework', 'Syllabi', 'Study Guides'];

        ResourceCategory::whereIn('name', array_merge($defaultTeacherCategories, $defaultStudentCategories))
            ->where('is_default', true)
            ->delete();
    }

    /**
     * Create default categories for a team
     */
    private function createDefaultCategoriesForTeam($teamId): void
    {
        // Teacher Materials
        $teacherCategories = [
            [
                'name' => 'Lesson Plans',
                'description' => 'Teaching plans, lecture notes, and class outlines',
                'color' => '#dc2626', // red-600
                'icon' => 'heroicon-o-clipboard-document-check',
                'sort_order' => 10,
            ],
            [
                'name' => 'Answer Keys',
                'description' => 'Solutions and answer keys for assignments and exams',
                'color' => '#ea580c', // orange-600
                'icon' => 'heroicon-o-key',
                'sort_order' => 20,
            ],
            [
                'name' => 'Worksheets',
                'description' => 'Teacher worksheets and activity templates',
                'color' => '#d97706', // amber-600
                'icon' => 'heroicon-o-document-duplicate',
                'sort_order' => 30,
            ],
            [
                'name' => 'Quiz Templates',
                'description' => 'Templates and question banks for quizzes',
                'color' => '#65a30d', // lime-600
                'icon' => 'heroicon-o-trophy',
                'sort_order' => 40,
            ],
            [
                'name' => 'Exams',
                'description' => 'Exam materials, test questions, and assessment tools',
                'color' => '#0284c7', // sky-600
                'icon' => 'heroicon-o-clipboard-document-list',
                'sort_order' => 50,
            ],
        ];

        // Student Resources
        $studentCategories = [
            [
                'name' => 'Handouts',
                'description' => 'Class handouts, notes, and distributed materials',
                'color' => '#2563eb', // blue-600
                'icon' => 'heroicon-o-document',
                'sort_order' => 60,
            ],
            [
                'name' => 'Reading Materials',
                'description' => 'Required and supplementary reading materials',
                'color' => '#7c3aed', // violet-600
                'icon' => 'heroicon-o-book-open',
                'sort_order' => 70,
            ],
            [
                'name' => 'Homework',
                'description' => 'Homework assignments and take-home activities',
                'color' => '#db2777', // pink-600
                'icon' => 'heroicon-o-pencil',
                'sort_order' => 80,
            ],
            [
                'name' => 'Syllabi',
                'description' => 'Course syllabi and class schedules',
                'color' => '#0891b2', // cyan-600
                'icon' => 'heroicon-o-calendar',
                'sort_order' => 90,
            ],
            [
                'name' => 'Study Guides',
                'description' => 'Study guides, review materials, and exam preparation',
                'color' => '#059669', // emerald-600
                'icon' => 'heroicon-o-academic-cap',
                'sort_order' => 100,
            ],
        ];

        // Insert Teacher Material categories
        foreach ($teacherCategories as $category) {
            ResourceCategory::firstOrCreate(
                [
                    'team_id' => $teamId,
                    'name' => $category['name'],
                    'is_default' => true,
                ],
                [
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'type' => 'teacher_material',
                ]
            );
        }

        // Insert Student Resource categories
        foreach ($studentCategories as $category) {
            ResourceCategory::firstOrCreate(
                [
                    'team_id' => $teamId,
                    'name' => $category['name'],
                    'is_default' => true,
                ],
                [
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'type' => 'student_resource',
                ]
            );
        }
    }
};
