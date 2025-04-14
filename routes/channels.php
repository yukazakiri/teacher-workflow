<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    Log::info('Attempting to authorize channel: channel.' . $channelId . ' for User ID: ' . $user->id);
    $channel = Channel::find($channelId);

    if (!$channel) {
        Log::warning('Channel authorization failed: Channel not found.', ['channelId' => $channelId]);
        return false;
    }

    // Check if the user is a member of the team that owns the channel
    $isMember = $user->belongsToTeam($channel->team);
    Log::info('Channel authorization result for channel.' . $channelId . ': ' . ($isMember ? 'Authorized' : 'Unauthorized'));
    return $isMember;
});
