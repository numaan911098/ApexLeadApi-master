<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormVariantSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'auto_navigation',
        'form_variant_id',
    ];
}
