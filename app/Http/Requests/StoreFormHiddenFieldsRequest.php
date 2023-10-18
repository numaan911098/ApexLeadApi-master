<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use Log;

class StoreFormHiddenFieldsRequest extends FormRequest
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
            'hiddenFields' => 'array',
            'hiddenFields.*.name' => 'required',
            'hiddenFields.*.capture_from_url_parameter' => 'required|boolean'
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
            // verify hidden field names are different.
            $hiddenFieldNames = [];

            if (empty($this->input('hiddenFields'))) {
                return;
            }

            foreach ($this->input('hiddenFields') as $hiddenField) {
                $hiddenFieldNames[] = $hiddenField['name'];
            }

            if (count(array_unique($hiddenFieldNames)) < count($hiddenFieldNames)) {
                $validator->errors()
                    ->add('hiddenField.name', 'Duplicate hiddenField name is found.');
                return;
            }

            // verify hiddenField and question field name conflict.
            $variant = $this->route('variant');

            if (empty($variant)) {
                return;
            }

            $variantState = $variant->buildState();

            $error = 'Duplicate hidden field name = "' . $hiddenField['name'];
            $error .= '" found. Question or Hidden field name should be different.';

            foreach ($variantState['steps'] as $step) {
                foreach ($step['questions'] as $question) {
                    if (empty($question['field_name'])) {
                        continue;
                    }
                    foreach ($this->input('hiddenFields') as $hiddenField) {
                        if ($hiddenField['name'] === $question['field_name']) {
                            $validator
                                ->errors()
                                ->add('hiddenField.name', $error);
                            return;
                        }
                    }
                }
            }
        });
    }
}
