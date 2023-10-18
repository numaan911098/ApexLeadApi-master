<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactState extends Model
{
     /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'enable',
        'landingpage_id',
        'secret_key',
        'form_id',
        'form_variant_id',
        'user_id',
    ];

    public function formVariant()
    {
        return $this->belongsTo('App\FormVariant');
    }
}
