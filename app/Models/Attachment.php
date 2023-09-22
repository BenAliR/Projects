<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'type',
        'path',
        'filename',
        'extension',

        'message_id'
    ];

    public function AttachmentMessage(){
        return $this->belongsTo('App\Models\Message','message_id');

    }
}
