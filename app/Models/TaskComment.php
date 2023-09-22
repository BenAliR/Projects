<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use HasFactory;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['body','user_id','task_id'];

    /**
     * Owner tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id','fullname','photo');
    }
    /**
     * Owner tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(task::class, 'task_id');
    }
}
