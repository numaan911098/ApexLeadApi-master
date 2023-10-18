<?php

namespace App\Http\Requests;

use App\Enums\BlacklistIp\IpMatchOperatorEnum;
use App\Enums\ErrorTypesEnum as ErrorType;
use Facades\App\Services\Util;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreBlacklistIpRequest extends FormRequest
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
        $rules = [
            'ip' => ['required'],
            'reason' => 'required',
            'operator' => [
                'required',
                Rule::in(IpMatchOperatorEnum::getConstants())
            ],
        ];

        if ($this->input('operator') === IpMatchOperatorEnum::EQUAL) {
            $rules['ip'][] = 'ipv4';
        }

        return $rules;
    }

    /**
     * @param Validator $validator
     */
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
