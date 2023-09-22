<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeEmail extends Model
{
    use HasFactory;
    protected $fillable = ['demande_id', 'subject','sujet','emailcontent'];

    public function email_demande(){
        return $this->belongsTo('App\Models\DemandeInscription','demande_id');

    }
}
