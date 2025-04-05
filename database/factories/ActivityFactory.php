<?php

namespace Database\Factories;

use App\Models\ActivityType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teacher = User::factory();
        $team = Team::factory();
        $activityType = ActivityType::factory();

        return [
            'teacher_id' => $teacher,
            'team_id' => $team,
            'activity_type_id' => $activityType,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'instructions' => $this->faker->paragraph,
            'format' => $this->faker->randomElement(['quiz', 'assignment', 'reporting', 'presentation', 'discussion', 'project', 'other']),
            'category' => $this->faker->randomElement(['written', 'performance']),
            'mode' => $this->faker->randomElement(['individual', 'group', 'take_home']),
            'total_points' => $this->faker->numberBetween(10, 100),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
        ];
    }
}
