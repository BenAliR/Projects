<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Lexx\ChatMessenger\Models\Participant;

class Thread extends \Lexx\ChatMessenger\Models\Thread
{
    use HasFactory;



    public function threadconnection()
    {

        return $this->hasOne('App\Models\ProjectThreads', 'thread_id')->orderBy('created_at', 'desc');
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }


}
