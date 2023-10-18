<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use App\Plan;

class StoreExternalCheckoutRequest extends FormRequest
{
    /**
     * Plan instance.
     *
     * @var Plan
     */
    private $planModel;

    /**
     * Constructor.
     *
     * @param ExternalCheckout $externalCheckoutModel
     */
    public function __construct(
        Plan $planModel
    ) {
        $this->planModel = $planModel;
    }
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
            'title'               => 'required',
            'description'         => 'required',
            'form_heading'        => 'required',
            'plan_id'             => 'required|integer',
            'fields'              => 'required|string',
            'redirect_url'        => 'nullable|url',
            'login'               => 'required|boolean',
            'enable'              => 'required|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validatePlan($validator, $this->input('plan_id'));
            $this->validateFields($validator, $this->input('fields'));
        });
    }

    protected function validatePlan($validator, $plan_id)
    {
        $plan = $this->planModel->where('id', $plan_id)->first();
        if (empty($plan->id) || $plan->external_checkout_enabled === 0) {
            $validator->errors()->add('plan_id', 'Invalid plan id submitted');
        }
    }

    protected function validateFields($validator, $data)
    {
        $fields = json_decode($data, true);
        if (is_null($fields)) {
            $validator->errors()->add('fields', 'The fields have invalid json data');
        }
        foreach ($fields as $field) {
            if (!isset($field['label'])) {
                $validator->errors()->add('label', 'The label field is invalid');
            }
            if (!isset($field['name'])) {
                $validator->errors()->add('name', 'The name field is invalid');
            }
            if (!isset($field['type'])) {
                $validator->errors()->add('type', 'The input type field is invalid');
            }
        }
        $ids = [];
        foreach ($fields as $field) {
            if (in_array($field['id'], $ids, true)) {
                $validator->errors()->add('id', 'The fields have duplicate id');
            }
            $ids[] = $field['id'];
        }
        $names = [];
        foreach ($fields as $field) {
            if (in_array($field['name'], $names, true)) {
                $validator->errors()->add('name', 'The fields have duplicate input field name');
            }
            $names[] = $field['name'];
        }
        foreach ($fields as $field) {
            if (!in_array($field['type'], ['text', 'email', 'password'], true)) {
                $validator->errors()->add('type', 'The fields have invalid input field type');
            }
        }
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
}
