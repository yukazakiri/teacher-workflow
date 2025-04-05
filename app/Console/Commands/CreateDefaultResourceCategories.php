<?php

namespace App\Console\Commands;

use App\Models\ResourceCategory;
use App\Models\Team;
use Illuminate\Console\Command;

class CreateDefaultResourceCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-default-resource-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default resource categories for all teams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teams = Team::all();
        $count = 0;

        $this->info('Creating default resource categories for teams...');
        $bar = $this->output->createProgressBar(count($teams));

        foreach ($teams as $team) {
            $createdCategories = $this->createDefaultCategoriesForTeam($team->id);
            $count += $createdCategories;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Created {$count} default resource categories for ".count($teams).' teams.');
    }

    /**
     * Create default resource categories for a team
     */
    private function createDefaultCategoriesForTeam(string $teamId): int
    {
        $createdCount = 0;

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
            $result = ResourceCategory::firstOrCreate(
                [
                    'team_id' => $teamId,
                    'name' => $category['name'],
                ],
                [
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'type' => 'teacher_material',
                    'is_default' => true,
                ]
            );

            if ($result->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        // Insert Student Resource categories
        foreach ($studentCategories as $category) {
            $result = ResourceCategory::firstOrCreate(
                [
                    'team_id' => $teamId,
                    'name' => $category['name'],
                ],
                [
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'type' => 'student_resource',
                    'is_default' => true,
                ]
            );

            if ($result->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }
}
