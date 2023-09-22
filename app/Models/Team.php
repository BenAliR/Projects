<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mpociot\Teamwork\TeamInvite;
use Mpociot\Teamwork\TeamworkTeam;

class Team extends TeamworkTeam
{
    use HasFactory;
    public function project()
    {

        return $this->hasOne('App\Models\Project', 'team_id');
    }
    public function TeamOwner()
    {

        return $this->belongsTo('App\Models\User', 'owner_id');
    }
    public function owner()
    {
            return $this->belongsTo(User::class, 'owner_id')->select('id','fullname','photo');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->select('id','fullname','photo','team_user.role');
    }
    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeamInvite::class)->select('id','email','created_at');
    }
}
