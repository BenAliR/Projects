<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Teamwork\TeamInvite;

class TeamInvitation extends TeamInvite
{
    use HasFactory;


    public function team(){
        return $this->belongsTo('App\Models\Team','team_id');

    }
    public function sender(){
        return $this->belongsTo('App\Models\User','user_id')->select('id','fullname','photo');

    }

}
