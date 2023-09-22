<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',

    ];
    public function projects() {
        return $this->belongsToMany(Project::class, 'project_tags');
    }

    public function tasks() {
        return $this->belongsToMany(Task::class, 'task_tags');
    }
}
