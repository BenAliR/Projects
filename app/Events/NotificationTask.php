<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotificationTask implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $task;

    public function __construct($user, $task)
    {
        $this->user = $user;
        $this->task = $task;
    }

    public function broadcastOn()
    {
        return new Channel('notifications.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'project.assigned';
    }
}
