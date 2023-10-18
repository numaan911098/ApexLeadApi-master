<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\FormExperimentTypesEnum;
use App\Enums\ExperimentStatesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Log;

class FormExperiment extends Model
{
    use SoftDeletes;
    use HasFactory;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'note',
        'form_id',
        'form_experiment_type_id'
    ];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function formExperimentVariants()
    {
        return $this->hasMany('App\FormExperimentVariant');
    }

    public function formExperimentType()
    {
        return $this->belongsTo('App\FormExperimentType');
    }

    // choose variant randomly based on current experiment running
    public function currentVariant()
    {
        if ($this->form->current_experiment_id) {
            $currentExperiment = $this->form->currentExperiment;

            $formExperimentType = $currentExperiment->formExperimentType;

            if ($formExperimentType->type === FormExperimentTypesEnum::AB) {
                $competitors = $currentExperiment
                ->formExperimentVariants
                ->where('weight', 50);

                if ($competitors->count() > 2) {
                    return null;
                }

                if ($competitors->first()->usage > $competitors->last()->usage) {
                    $competitors->last()->usage++;

                    $competitors->last()->save();

                    return $competitors->last()->formVariant;
                } else {
                    $competitors->first()->usage++;

                    $competitors->first()->save();

                    return $competitors->first()->formVariant;
                }
            }
        }

        return null;
    }


    public function state()
    {
        if ($this->started_at && $this->ended_at) {
            return ExperimentStatesEnum::ENDED;
        } elseif ($this->started_at && !$this->ended_at) {
            return ExperimentStatesEnum::RUNNING;
        } elseif (!$this->started_at && !$this->ended_at) {
            return ExperimentStatesEnum::DRAFT;
        }

        return null;
    }
}
