<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use App\Enums\MimeEnum;
use App\Enums\FormWebhookMethodsEnum;
use App\FormVariant;
use App\FormQuestion;
use App\FormHiddenField;
use Log;

class StoreFormWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'url' => 'required|url',
            'enable' => 'boolean',
            'format' => 'required',
            'method' => 'required'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Util::apiResponse(
            422,
            [],
            ErrorType::INVALID_DATA,
            'Invalid data submitted',
            $validator->errors()->toArray()
        ));
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // validate format
            if (!in_array($this->input('format'), MimeEnum::getConstants())) {
                $validator->errors()->add('format', 'Invalid Format');
            }

            // validate method
            if (!in_array($this->input('method'), FormWebhookMethodsEnum::getConstants())) {
                $validator->errors()->add('method', 'Invalid webhook method');
            }

            // validate variant
            if (!empty($this->input('form_variant_id'))) {
                $formVariant = FormVariant::find($this->input('form_variant_id'));
                if (empty($formVariant)) {
                    $validator->errors()->add('form_variant_id', 'Invalid id');
                    return;
                }

                // validate fields_map
                $formVariantState = $formVariant->buildState();

                $defaultFieldNames = [];
                $sIndex = 1;
                foreach ($formVariantState['steps'] as $step) {
                    $qIndex = 1;
                    if (!empty($step['questions'])) {
                        foreach ($step['questions'] as $question) {
                            $defaultFieldNames[] = 'S' . $sIndex . '_Q' . $qIndex;
                            $qIndex++;
                        }
                    }
                    $sIndex++;
                }

                if (!empty($this->input('fields_map'))) {
                    $toFieldNames = [];

                    foreach ($this->input('fields_map') as $fieldMap) {
                        if (!empty($fieldMap['questionId'])) {
                            if (empty(FormQuestion::find($fieldMap['questionId']))) {
                                $validator->errors()->add('fields_map', 'questionId is invalid');
                                return;
                            }
                        } elseif (!empty($fieldMap['hiddenFieldId'])) {
                            if (empty(FormHiddenField::find($fieldMap['hiddenFieldId']))) {
                                $validator->errors()->add('fields_map', 'hiddenFieldId is invalid');
                                return;
                            }
                        }

                        if (empty($fieldMap['to'])) {
                            $validator->errors()->add('fields_map', 'to field is required');
                            return;
                        }

                        if (empty($fieldMap['from'])) {
                            $validator->errors()->add('fields_map', 'from field is required');
                            return;
                        }

                        $error = 'Question from field value is invalid, expected values should be in S*_Q* format ';
                        $error .= 'where * is unsigned integer starting from 1.';

                        $toFieldNames[] = $fieldMap['to'];
                        if (!empty($fieldMap['questionId'])) {
                            if (!in_array($fieldMap['from'], $defaultFieldNames)) {
                                $validator->errors()
                                ->add(
                                    'fields_map',
                                    $error
                                );
                            }
                        }
                    }

                    if (count(array_unique($toFieldNames)) < count($toFieldNames)) {
                        $validator->errors()->add('fields_map', 'duplicate to field found.');
                    }

                    if (in_array($formVariantState['calculator']['fieldName'], $toFieldNames)) {
                        $validator->errors()->add('fields_map', 'calculator field already found.');
                    }
                }
            }
        });
    }
}
