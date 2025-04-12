<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\ChannelCategory;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teams
        $teams = Team::all();

        foreach ($teams as $team) {
            // Create default categories for each team
            $generalCategory = ChannelCategory::create([
                'team_id' => $team->id,
                'name' => 'General',
                'position' => 0,
            ]);

            $classworkCategory = ChannelCategory::create([
                'team_id' => $team->id,
                'name' => 'Classwork',
                'position' => 1,
            ]);

            // Create default channels for each team
            $generalChannel = Channel::create([
                'team_id' => $team->id,
                'category_id' => $generalCategory->id,
                'name' => 'general',
                'slug' => 'general',
                'description' => 'General discussion channel',
                'type' => 'text',
                'is_private' => false,
                'position' => 0,
            ]);

            $announcementsChannel = Channel::create([
                'team_id' => $team->id,
                'category_id' => $generalCategory->id,
                'name' => 'announcements',
                'slug' => 'announcements',
                'description' => 'Important announcements',
                'type' => 'announcement',
                'is_private' => false,
                'position' => 1,
            ]);

            $homeworkChannel = Channel::create([
                'team_id' => $team->id,
                'category_id' => $classworkCategory->id,
                'name' => 'homework',
                'slug' => 'homework',
                'description' => 'Homework discussion',
                'type' => 'text',
                'is_private' => false,
                'position' => 0,
            ]);

            $questionsChannel = Channel::create([
                'team_id' => $team->id,
                'category_id' => $classworkCategory->id,
                'name' => 'questions',
                'slug' => 'questions',
                'description' => 'Ask questions about the class',
                'type' => 'text',
                'is_private' => false,
                'position' => 1,
            ]);

            // Add all team members to the channels
            $teamMembers = $team->users;
            
            foreach ($teamMembers as $member) {
                $generalChannel->members()->attach($member->id);
                $announcementsChannel->members()->attach($member->id);
                $homeworkChannel->members()->attach($member->id);
                $questionsChannel->members()->attach($member->id);
                
                // Add some sample messages from team owner
                if ($member->id === $team->user_id) {
                    Message::create([
                        'channel_id' => $generalChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Welcome to the ' . $team->name . ' chat!',
                    ]);
                    
                    Message::create([
                        'channel_id' => $announcementsChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Important: Please check the homework channel for your assignments.',
                    ]);
                    
                    Message::create([
                        'channel_id' => $homeworkChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Your first assignment is due next week.',
                    ]);
                }
            }
        }
    }
}
