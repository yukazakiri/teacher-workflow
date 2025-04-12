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

            $resourcesCategory = ChannelCategory::create([
                'team_id' => $team->id,
                'name' => 'Resources',
                'position' => 2,
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
            
            $filesChannel = Channel::create([
                'team_id' => $team->id,
                'category_id' => $resourcesCategory->id,
                'name' => 'files',
                'slug' => 'files',
                'description' => 'Share files and resources',
                'type' => 'media',
                'is_private' => false,
                'position' => 0,
            ]);
            
            $linksChannel = Channel::create([
                'team_id' => $team->id,
                'category_id' => $resourcesCategory->id,
                'name' => 'links',
                'slug' => 'links',
                'description' => 'Share useful links',
                'type' => 'text',
                'is_private' => false,
                'position' => 1,
            ]);

            // Add all team members to the channels
            $teamMembers = $team->users;
            $teamOwner = User::find($team->user_id);
            
            foreach ($teamMembers as $member) {
                // Default permissions for all members
                $memberPermissions = 'read,write'; 
                
                // Extended permissions for team owner
                if ($member->id === $team->user_id) {
                    $memberPermissions = 'read,write,manage';
                }
                
                $generalChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                $announcementsChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                $homeworkChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                $questionsChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                $filesChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                $linksChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                
                // Add some sample messages from team owner
                if ($member->id === $team->user_id) {
                    Message::create([
                        'channel_id' => $generalChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Welcome to the ' . $team->name . ' chat! Feel free to create new channels as needed.',
                    ]);
                    
                    Message::create([
                        'channel_id' => $announcementsChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Important: Please check the homework channel for your assignments.',
                    ]);
                    
                    Message::create([
                        'channel_id' => $homeworkChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Your first assignment is due next week. You can use this channel to ask questions about the assignments.',
                    ]);
                    
                    Message::create([
                        'channel_id' => $filesChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Upload course materials and resources here.',
                    ]);
                    
                    Message::create([
                        'channel_id' => $linksChannel->id,
                        'user_id' => $member->id,
                        'content' => 'Share useful websites and resources for the course here.',
                    ]);
                }
            }
        }
    }
}
