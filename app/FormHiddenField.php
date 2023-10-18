<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormHiddenField extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'name',
        'default_value',
        'capture_from_url_parameter',
        'form_id',
        'form_variant_id',
    ];

    public function hiddenFieldResponses()
    {
        return $this->hasMany('App\FormHiddenFieldResponse');
    }
}
