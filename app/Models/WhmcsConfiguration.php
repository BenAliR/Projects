<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhmcsConfiguration extends Model
{
    use HasFactory;
    protected $fillable = [



        'wh_username_client',
        'wh_password_client',
        'wh_accesskey',
        'wh_url'];
}
