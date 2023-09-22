<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriteriaScore extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
     'evaluation_criteria_id',
'project_id',
'user_id',
'score',

    ];
    public function criteria() {
        return $this->belongsTo(EvaluationCriteria::class, 'evaluation_criteria_id');
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

// Add other relationships as needed

}
