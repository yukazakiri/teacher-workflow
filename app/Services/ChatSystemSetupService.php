<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\Channel;
use App\Models\ChannelCategory;
use App\Models\Message;
use App\Models\User;

class ChatSystemSetupService
{
    public function setupForTeam(Team $team): void
    {
        // Create categories
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

        // Create channels
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

        // Add members to channels
        $teamMembers = $team->users;
        foreach ($teamMembers as $member) {
            $memberPermissions = '["read", "write"]';
            if ($member->id === $team->user_id) {
                $memberPermissions = '["read", "write", "manage"]';
            }
            foreach ([$generalChannel, $announcementsChannel, $homeworkChannel, $questionsChannel, $filesChannel, $linksChannel] as $channel) {
                if (!$channel->members()->where('user_id', $member->id)->exists()) {
                    $channel->members()->attach($member->id, ['permissions' => $memberPermissions]);
                }
            }
        }

        // Add sample messages from team owner if they don't exist
        $owner = User::find($team->user_id);
        if ($owner && !Message::where('channel_id', $generalChannel->id)->exists()) {
            Message::create([
                'channel_id' => $generalChannel->id,
                'user_id' => $owner->id,
                'content' => 'Welcome to the ' . $team->name . ' chat! Feel free to create new channels as needed.',
            ]);
            Message::create([
                'channel_id' => $announcementsChannel->id,
                'user_id' => $owner->id,
                'content' => 'Important: Please check the homework channel for your assignments.',
            ]);
            Message::create([
                'channel_id' => $homeworkChannel->id,
                'user_id' => $owner->id,
                'content' => 'Your first assignment is due next week. You can use this channel to ask questions about the assignments.',
            ]);
            Message::create([
                'channel_id' => $filesChannel->id,
                'user_id' => $owner->id,
                'content' => 'Upload course materials and resources here.',
            ]);
            Message::create([
                'channel_id' => $linksChannel->id,
                'user_id' => $owner->id,
                'content' => 'Share useful websites and resources for the course here.',
            ]);
        }
    }
}
