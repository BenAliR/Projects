<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteAttachment extends Model
{
    use HasFactory;
    protected $fillable = [
        'telephone',
        'email',
        'photo',
        'nom',
        'prenom',
        'adresse',
        'ville',
        'cite',
        'codepostal',
        'etablissement',
        'profession',
        'user_id',

    ];
}
