<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'google_id',
        'access_token',
        'expires_in',
        'user_id'
    ];
}
