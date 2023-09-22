<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends  \Lexx\ChatMessenger\Models\Message
{
    use HasFactory;
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {

        return $this->hasMany('App\Models\Attachment', 'message_id')->orderBy('created_at', 'desc');
    }
}
