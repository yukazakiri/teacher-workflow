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
            // Create default categories for each team if they don't exist
            $generalCategory = ChannelCategory::firstOrCreate(
                ['team_id' => $team->id, 'name' => 'General'],
                ['position' => 0]
            );

            $classworkCategory = ChannelCategory::firstOrCreate(
                ['team_id' => $team->id, 'name' => 'Classwork'],
                ['position' => 1]
            );

            $resourcesCategory = ChannelCategory::firstOrCreate(
                ['team_id' => $team->id, 'name' => 'Resources'],
                ['position' => 2]
            );

            // Create default channels for each team if they don't exist
            $generalChannel = Channel::firstOrCreate(
                ['team_id' => $team->id, 'slug' => 'general'],
                [
                    'category_id' => $generalCategory->id,
                    'name' => 'general',
                    'description' => 'General discussion channel',
                    'type' => 'text',
                    'is_private' => false,
                    'position' => 0,
                ]
            );

            $announcementsChannel = Channel::firstOrCreate(
                ['team_id' => $team->id, 'slug' => 'announcements'],
                [
                    'category_id' => $generalCategory->id,
                    'name' => 'announcements',
                    'description' => 'Important announcements',
                    'type' => 'announcement',
                    'is_private' => false,
                    'position' => 1,
                ]
            );

            $homeworkChannel = Channel::firstOrCreate(
                ['team_id' => $team->id, 'slug' => 'homework'],
                [
                    'category_id' => $classworkCategory->id,
                    'name' => 'homework',
                    'description' => 'Homework discussion',
                    'type' => 'text',
                    'is_private' => false,
                    'position' => 0,
                ]
            );

            $questionsChannel = Channel::firstOrCreate(
                ['team_id' => $team->id, 'slug' => 'questions'],
                [
                    'category_id' => $classworkCategory->id,
                    'name' => 'questions',
                    'description' => 'Ask questions about the class',
                    'type' => 'text',
                    'is_private' => false,
                    'position' => 1,
                ]
            );
            
            $filesChannel = Channel::firstOrCreate(
                ['team_id' => $team->id, 'slug' => 'files'],
                [
                    'category_id' => $resourcesCategory->id,
                    'name' => 'files',
                    'description' => 'Share files and resources',
                    'type' => 'media',
                    'is_private' => false,
                    'position' => 0,
                ]
            );
            
            $linksChannel = Channel::firstOrCreate(
                ['team_id' => $team->id, 'slug' => 'links'],
                [
                    'category_id' => $resourcesCategory->id,
                    'name' => 'links',
                    'description' => 'Share useful links',
                    'type' => 'text',
                    'is_private' => false,
                    'position' => 1,
                ]
            );

            // Add team members to channels if they're not already members
            $teamMembers = $team->users;
            $teamOwner = User::find($team->user_id);
            
            foreach ($teamMembers as $member) {
                // Default permissions for all members in JSON format
                $memberPermissions = '["read", "write"]'; 
                
                // Extended permissions for team owner in JSON format
                if ($member->id === $team->user_id) {
                    $memberPermissions = '["read", "write", "manage"]';
                }
                
                // Only attach member if not already attached
                if (!$generalChannel->members()->where('user_id', $member->id)->exists()) {
                    $generalChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
                
                if (!$announcementsChannel->members()->where('user_id', $member->id)->exists()) {
                    $announcementsChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
                
                if (!$homeworkChannel->members()->where('user_id', $member->id)->exists()) {
                    $homeworkChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
                
                if (!$questionsChannel->members()->where('user_id', $member->id)->exists()) {
                    $questionsChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
                
                if (!$filesChannel->members()->where('user_id', $member->id)->exists()) {
                    $filesChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
                
                if (!$linksChannel->members()->where('user_id', $member->id)->exists()) {
                    $linksChannel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
                
                // Add sample messages from team owner if they don't exist
                if ($member->id === $team->user_id && !Message::where('channel_id', $generalChannel->id)->exists()) {
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
