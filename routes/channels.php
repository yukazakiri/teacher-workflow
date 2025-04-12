<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    $channel = Channel::find($channelId);

    if (!$channel) {
        return false;
    }

    // Check if the user is a member of the team that owns the channel
    return $user->belongsToTeam($channel->team);
});
