<?php

namespace App\Models;

use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Lexx\ChatMessenger\Traits\Messagable;
use Mpociot\Teamwork\Traits\UserHasTeams;
use Illuminate\Support\Facades\Mail;
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable,UserHasTeams,Messagable,SoftDeletes; // Add this trait to your model
    public static function generatePassword()
    {
        // Generate random string and encrypt it.
        return bcrypt(str_random(35));
    }

    public static function sendWelcomeEmail($user)
    {
        // Generate a new reset password token
        $token = app('auth.password.broker')->createToken($user);

        // Send email
        Mail::send('emails.welcome', ['user' => $user, 'token' => $token], function ($m) use ($user) {
            $m->from('email@email.com.com', 'Your App Name');

            $m->to($user->email, $user->nom)->subject('Welcome to APP');
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'fullname',
        'nom',
        'prenom',
        'email',
        'password',
        'wh_id',
        'role',
        'last_login_at',
        'last_login_ip',
        'banned',
        'banned_at',

    ];
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }
    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    public function demande()
    {
        return $this->hasOne(DemandeInscription::class, 'user_id');
    }
    public function encadrement()
    {
        return $this->hasOne(InviteAttachment::class, 'user_id');
    }
    public function projects()
    {

        return $this->hasMany('App\Models\Project', 'user_id')->orderBy('created_at', 'desc');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',

        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
