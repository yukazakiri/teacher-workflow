<?php

namespace App\Notifications;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChannelNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The action that occurred.
     */
    protected string $action;

    /**
     * The channel that was affected.
     */
    protected Channel $channel;

    /**
     * The user who performed the action.
     */
    protected User $performer;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $action, Channel $channel, User $performer)
    {
        $this->action = $action;
        $this->channel = $channel;
        $this->performer = $performer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Channel {$this->action}: {$this->channel->name}")
            ->line("{$this->performer->name} has {$this->action} the channel '{$this->channel->name}' in team '{$this->channel->team->name}'.")
            ->action('View Channel', url('/teams/' . $this->channel->team->id . '/chat/' . $this->channel->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => $this->action,
            'channel_id' => $this->channel->id,
            'channel_name' => $this->channel->name,
            'team_id' => $this->channel->team_id,
            'team_name' => $this->channel->team->name,
            'performer_id' => $this->performer->id,
            'performer_name' => $this->performer->name,
        ];
    }
}
