<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Onboarding extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'page',
        'complete',
    ];
}
