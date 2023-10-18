<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormHiddenFieldResponse extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'response',
        'form_hidden_field_id',
        'form_lead_id',
    ];

    public function formHiddenField()
    {
        return $this->belongsTo('App\FormHiddenField');
    }

    public function formLead()
    {
        return $this->belongsTo('App\FormLead');
    }
}
