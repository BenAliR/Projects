<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sharedData extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'token',
        'status',

        'project_id',
    ];
    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }
}
