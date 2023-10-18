<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Http\Requests\StoreFormWebhook;

class FormWebhook extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'enable',
        'url',
        'format',
        'method',
        'fields_map',
        'form_id',
        'form_variant_id',
        'headers',
        'secret'
    ];

    public function formVariant()
    {
        return $this->belongsTo('App\FormVariant');
    }

    public function getFieldsMapAttribute($value)
    {
        return json_decode($value, true);
    }

    public function formWebhookRequests()
    {
        return $this->hasMany('App\FormWebhookRequest');
    }
}
