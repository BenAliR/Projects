<?php
namespace App\Events;
// app/Notifications/AssignmentNotification.php

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AssignmentNotification extends Notification
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
    public function broadcastOn()
    {
        return 'assignment-channel'; // Replace with your desired channel name.
    }
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => $this->message,
        ]);
    }

    // Define the "via" method to specify the broadcasting channel.
    public function via($notifiable)
    {
        return ['broadcast']; // Use the "broadcast" channel.
    }
}
