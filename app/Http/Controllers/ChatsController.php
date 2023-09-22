<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NotificationEvent;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ChatsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show chats
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('chat');
    }

    /**
     * Fetch all messages
     *
     * @return Message
     */
    public function fetchMessages()
    {
        return Message::with('user')->get();
    }

    /**
     * Persist message to database
     *
     * @param  Request $request
     * @return Response
     */
    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        $message = $user->messages()->create([
            'message' => $request->input('message')
        ]);
        $notification = "[
            'title' => 'New Notification',
            'message' => 'You have a new notification!',
        ]";

        event(new NotificationEvent($notification));
        broadcast(new MessageSent($user, $message))->toOthers();

        return ['status' => 'Message Sent!'];
    }
}
