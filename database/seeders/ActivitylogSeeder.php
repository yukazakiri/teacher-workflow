<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
class ActivitylogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run(): void
    {
        // Get all existing users to use as causers and subjects
        $users = User::all();

        // Define the different log types you want to use
        $logTypes = [
            'user_management',
            'content_updates',
            'system_events',
            'order_processing', // Added a fourth type for more variety
        ];

        // Ensure there are users to create activity logs
        if ($users->isEmpty()) {
            $this->command->info('No users found. Please seed users first.');
            return;
        }

        // Number of activity logs to create
        $numberOfLogs = 50;

        // Loop to create activity logs
        for ($i = 0; $i < $numberOfLogs; $i++) {
            // Randomly select a user to be the causer (the one who performed the action)
            $causer = $users->random();

            // Randomly select a user to be the subject (the model the action was performed on)
            // You can modify this to include other model types if needed
            $subject = $users->random();

            // Randomly select a log type
            $logName = $logTypes[array_rand($logTypes)];

            // Define a description for the activity
            $description = "Sample action related to {$logName}";

            // Define some sample properties (optional)
            $properties = [
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Sample Browser/OS Info',
                'old' => ['field' => 'old_value_' . rand(1, 100)],
                'attributes' => ['field' => 'new_value_' . rand(1, 100)],
            ];

            // Create the activity log entry
            activity($logName)
                ->performedOn($subject) // The model the action was performed on
                ->causedBy($causer) // The user who performed the action
                ->withProperties($properties) // Additional data
                ->log($description); // The description of the activity
        }

        $this->command->info("Seeded {$numberOfLogs} activity logs.");
    }
}
