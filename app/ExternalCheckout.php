<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalCheckout extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'ref_id',
        'description',
        'form_heading',
        'plan_id',
        'fields',
        'redirect_url',
        'login',
        'enable',
    ];
}
