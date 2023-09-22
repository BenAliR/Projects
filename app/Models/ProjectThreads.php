<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectThreads extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'thread_id',
        'project_id',
    ];
    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }

    public function thread()
    {
        return $this->belongsTo('App\Models\Thread','thread_id');
    }
}
