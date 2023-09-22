<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;


class Notification extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'title','message', 'type', 'read','read_at','notifiable','data'];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mark the notification as read
    public function markAsRead()
    {
        $this->update(['read' => true,'read_at' => new Date()]);
    }

    // Check if the notification is unread
    public function isUnread()
    {
        return !$this->read;
    }

    // Scope to retrieve only unread notifications
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    // Scope to retrieve notifications of a specific type
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
