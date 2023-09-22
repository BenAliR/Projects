<?php
namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotificationEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): Channel
    {
        info("hello");
        return new Channel('notifications-development');
    }

    public function broadcastAs(): string
    {
        return 'new-notification';
    }
}
