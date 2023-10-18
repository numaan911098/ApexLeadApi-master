<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Enums\QuestionTypesEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Log;

class FormQuestionResponse extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'response',
        'form_question_id',
        'form_lead_id',
    ];

    public function getResponseAttribute($value)
    {
        $questionType = $this->formQuestion->formQuestionType->type;

        if ($questionType === QuestionTypesEnum::ADDRESS) {
            return json_decode($value, true);
        } elseif (
            $questionType === QuestionTypesEnum::SINGLE_CHOICE ||
            $questionType === QuestionTypesEnum::MULTIPLE_CHOICE ||
            $questionType === QuestionTypesEnum::GDPR
        ) {
            return json_decode($value, true) ?? $value;
        } else {
            return $value;
        }
    }

    public function formQuestion()
    {
        return $this->belongsTo('App\FormQuestion');
    }

    public function formLead()
    {
        return $this->belongsTo('App\FormLead');
    }

    public function responseString(FormQuestionResponse $questionResponse)
    {
        $questionType = $questionResponse->formQuestion->formQuestionType->type;
        $response = '';

        if ($questionType === QuestionTypesEnum::ADDRESS) {
            $addressFields = $questionResponse->response;

            $fieldsEnabled = array_reduce($this->formQuestion->config['fields'], function ($c, $f) {
                if ($f['enabled'] === 'true') {
                    array_push($c, $f['id']);
                }
                return $c;
            }, []);

            if (count($fieldsEnabled) === 1) {
                $response = $addressFields[$fieldsEnabled[0]];
            } elseif (count($fieldsEnabled) > 1) {
                foreach ($addressFields as $addressFieldKey => $addressFieldValue) {
                    if (!in_array($addressFieldKey, $fieldsEnabled)) {
                        continue;
                    }
                    $response .= "$addressFieldKey: $addressFieldValue\n\n";
                }
            }
        } elseif ($questionType === QuestionTypesEnum::MULTIPLE_CHOICE) {
            if (is_array($questionResponse->response)) {
                $response = array_map(function ($choice) {
                    return $choice['label'];
                }, $questionResponse->response);
                $response = implode(', ', $response);
            } else {
                $response = $questionResponse->response;
            }
        } elseif ($questionType === QuestionTypesEnum::SINGLE_CHOICE) {
            if (is_array($questionResponse->response)) {
                $response = $questionResponse->response['label'];
            } else {
                $response = $questionResponse->response;
            }
        } elseif ($questionType === QuestionTypesEnum::GDPR) {
            if (is_array($questionResponse->response)) {
                if (count($questionResponse->response) > 0 && is_array($questionResponse->response[0])) {
                    $response = implode(', ', array_map(function ($choice, $index) {
                        return 'option ' . ($index + 1);
                    }, $questionResponse->response, array_keys($questionResponse->response)));
                } else {
                    $response = implode(', ', $questionResponse->response);
                }
            } else {
                $response = $questionResponse->response;
            }
        } elseif ($questionType === QuestionTypesEnum::DATE && !empty($questionResponse->response)) {
            $response = Carbon::parse($questionResponse->response);
            $response = $response->format('d F Y');
        } else {
            $response = $questionResponse->response;
        }

        return $response;
    }
}
