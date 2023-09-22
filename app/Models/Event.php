<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class Event extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
    'title',
'description',
'start_datetime',
'end_datetime',
'project_id',
'task_id',
    ];

    public function task() {
        return $this->belongsTo(Task::class,'task_id');
    }
    public function project() {
        return $this->belongsTo(Project::class,'project_id');
    }

    public function activity() {
        return $this->hasMany(Activity::class,'subject_id');
    }
}
