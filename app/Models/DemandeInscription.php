<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeInscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'telephone',
        'email',
        'photo',
        'country',
        'typeecole',
        'nom',
        'prenom',
        'adresse',
        'adresse2',
        'ville',
        'cite',
        'codepostal',
        'copie1',
        'copie2',
        'copie3',
        'copie4',
        'demande_status',
        'etablisement',
        'user_id',
        'user_type',
        'id_code',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'user_type'

    ];

    public function DemandeEmails()
    {

        return $this->hasMany('App\Models\DemandeEmail', 'demande_id')->orderBy('created_at', 'desc');
    }
    /**
     * Get the parent commentable model (post or video).
     */
    public function user()
    {
        return $this->morphTo();
    }
}
