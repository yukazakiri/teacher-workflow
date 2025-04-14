<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message instance.
     *
     * @var \App\Models\Message
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channelName = 'channel.' . $this->message->channel_id;
        \Illuminate\Support\Facades\Log::info('Broadcasting MessageSent event on channel: ' . $channelName, ['message_id' => $this->message->id]);
        return [
            new PrivateChannel($channelName),
        ];
    }
    
    /**
     * Get the event name that should be broadcast.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'channel_id' => $this->message->channel_id,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'avatar' => $this->message->user->profile_photo_url,
            ],
            'created_at' => $this->message->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->message->created_at->diffForHumans(),
            'is_edited' => $this->message->edited_at !== null,
        ];
    }
}
