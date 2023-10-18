<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhiteLabel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'enabled',
        'user_id'
    ];
}
