<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'content',
'project_id',
        'user_id',
    ];
    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

}
