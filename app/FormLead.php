<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\QuestionTypesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Log;

class FormLead extends Model
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
        'reference_no',
        'form_variant_id',
        'form_visit_id',
        'form_id',
        'form_experiment_id',
        'calculator_total',
        'claim_url',
        'is_partial'
    ];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function formVisit()
    {
        return $this->belongsTo('App\FormVisit');
    }

    public function questionResponses()
    {
        return $this->hasMany('App\FormQuestionResponse');
    }

    public function hiddenFieldResponses()
    {
        return $this->hasMany('App\FormHiddenFieldResponse');
    }

    public function formVariant()
    {
        return $this->belongsTo('App\FormVariant');
    }

    public function replyToEmailAddresses()
    {
        $addresses = [];
        $responses = $this->questionResponses;

        foreach ($responses as $response) {
            $config = $response->formQuestion->config;

            if ($config['type'] !== QuestionTypesEnum::EMAIL_ADDRESS) {
                continue;
            }

            if (empty($response->response)) {
                continue;
            }

            if ($this->form->formEmailNotification->reply_to) {
                $addresses[] = $response->response;

                continue;
            }

            if (!isset($config['replyTo']) ||  $config['replyTo'] !== 'true') {
                continue;
            }

            $addresses[] = $response->response;
        }

        return $addresses;
    }

    public function getCalculatorTotalAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        return round($value, 2);
    }
}
