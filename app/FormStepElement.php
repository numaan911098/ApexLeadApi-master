<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormStepElement extends Model
{
    protected $fillable = [
        'config',
        'number',
        'type',
        'form_step_id'
    ];

    public function buildState()
    {
        $config = $this->config;
        $config['dbId'] = $this->id;
        $config['id'] = (int) $config['id'];
        $config['stepId'] = (int) $this->formStep->number;
        $config['number'] = (int) $this->number;
        return $config;
    }

    public function getState(array $element, array $step): array
    {
        $config = json_decode($element['config'], true);
        $config['dbId'] = $element['id'];
        $config['id'] = (int) $config['id'];
        $config['stepId'] = (int) $step['number'];
        $config['number'] = (int) $element['number'];

        return $config;
    }

    /**
     * The step this element belongs to.
     */
    public function formStep()
    {
        return $this->belongsTo('App\FormStep');
    }

    public function getConfigAttribute($value)
    {
        return json_decode($value, true);
    }
}
