<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel("App.Models.User.{id}", function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel("channel.{channelId}", function ($user, $channelId) {
    // Log entry point
    Log::info(
        "Attempting to authorize channel: channel." .
            $channelId .
            " for User ID: " .
            $user->id
    );

    $channel = Channel::find($channelId);

    if (!$channel) {
        Log::warning("Channel authorization failed: Channel not found.", [
            "channelId" => $channelId,
        ]);
        return false; // Deny if channel doesn't exist
    }

    // Log channel and team info
    Log::debug("Channel Found:", [
        "channel_id" => $channel->id,
        "channel_name" => $channel->name,
        "team_id" => $channel->team_id, // Make sure team_id exists
    ]);
    Log::debug("User Info:", [
        "user_id" => $user->id,
        "user_current_team_id" => $user->current_team_id ?? "N/A", // Handle potential null
    ]);

    // Log all teams the user belongs to (if relationship exists)
    if (method_exists($user, "allTeams")) {
        Log::debug('User\'s Teams:', $user->allTeams()->pluck("id")->toArray());
    } else {
        Log::debug("User model does not have allTeams method.");
    }

    // Eager load the team relationship to be sure it's available
    $channel->load("team");
    if (!$channel->team) {
        Log::error(
            "Channel authorization failed: Channel team relationship not loaded or null.",
            ["channel_id" => $channel->id]
        );
        return false; // Cannot authorize without a team
    }

    // Check membership using the relationship
    $isMember = $user->belongsToTeam($channel->team);

    Log::info(
        "Channel authorization result for channel." .
            $channelId .
            ": " .
            ($isMember ? "Authorized" : "Unauthorized")
    );

    // Explicitly return true or false
    return (bool) $isMember; // Return true ONLY if $isMember is true
});
