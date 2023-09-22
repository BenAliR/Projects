<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppInfoConfiguration extends Model
{
    use HasFactory;
    protected $fillable = [

        'app_name',
        'app_url',
        'app_logo',
        'url_facebook',
        'url_linkedin',
        'url_instagram',
        'url_website',

 ];
}
