<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\{ErrorTypesEnum, CredentialTypesEnum};
use Illuminate\Validation\Rule;

class UpdateCredentialRequest extends FormRequest
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
            'credentials.*.title' => 'required',
            'credentials.*.type' => [
                'required',
                Rule::in(CredentialTypesEnum::getConstants())
            ],
            'credentials.*.config' => 'required',
            'credentials.*.enabled' => 'required|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Util::apiResponse(
            422,
            [],
            ErrorTypesEnum::INVALID_DATA,
            'Invalid data submitted',
            $validator->errors()->toArray()
        ));
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $credentials = $this->input('credentials');

            foreach ($credentials as $credential) {
                if (empty($credential['config']) || !is_array($credential['config'])) {
                    $validator
                        ->errors()
                        ->add('config', 'Invalid Config');
                }

                $config = $credential['config'];
            }
        });
    }
}
