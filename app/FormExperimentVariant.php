<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormExperimentVariant extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'weight',
        'usage',
        'form_experiment_id',
        'form_variant_id'
    ];

    public function formExperiment()
    {
        return $this->belongsTo('App\FormExperiment');
    }

    public function formVariant()
    {
        return $this->belongsTo('App\FormVariant');
    }
}
