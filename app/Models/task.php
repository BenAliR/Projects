<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cviebrock\EloquentSluggable\Sluggable;
class task extends Model
{
    use HasFactory, SoftDeletes ,Sluggable;
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
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['parent_id', 'title', 'description','priority','slug','status','type', 'tags','due_date','user_id','assign_id','project_id'];

    /**
     * Main tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    /**
     * Owner tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id','fullname','photo');
    }
    /**
     * AssignTo tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Assigned()
    {
        return $this->belongsTo(User::class, 'assign_id')->select('id','fullname','photo');
    }
    /**
     * AssignTo tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
    /**
     * Subtasks associated with task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subtasks()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('due_date','desc');
    }
    /**
     * Comment associated with task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('updated_at','desc');
    }
}
