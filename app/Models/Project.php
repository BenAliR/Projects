<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory,
        SoftDeletes,
     Sluggable;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'description',
        'project_avatar',
        'project_status',
        'type',
        'dev_technologie',
        'domaine',
        'user_id',
        'team_size',
        'serviceid',
        'slug',
        'duedate',
        'team_id'
    ];
    public function tasks()
    {
        return $this->hasMany('App\Models\task', 'project_id')->orderBy('due_date', 'desc');

    }

    public function meetings()
    {

        return $this->hasMany('App\Models\Meeting', 'project_id')->orderBy('start_datetime', 'desc');
    }



    public function ProjectThreads()
    {

        return $this->hasMany('App\Models\ProjectThreads', 'project_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the shared data record associated with the project.
     */
    public function Shared()
    {
        return $this->hasOne('App\Models\sharedData', 'project_id');
    }
    public function team(){
        return $this->belongsTo('App\Models\Team','team_id');

    }
    public function owner(){
        return $this->belongsTo('App\Models\User','user_id')->select('id','fullname','photo');

    }
    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
}
