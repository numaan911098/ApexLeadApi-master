<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'attempted_at',
        'browser',
        'device',
        'ip',
        'os',
        'country',
        'state',
        'city'
    ];
}
