<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalCheckoutLog extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'response',
        'external_checkout_id',
    ];
}
