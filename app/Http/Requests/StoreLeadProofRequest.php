<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;

class StoreLeadProofRequest extends FormRequest
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
            'title'               => 'required',
            'description'         => 'required',
            'form_variant_id'     => 'required|integer',
            'form_question_id'    => 'required|integer',
            'count'               => 'required|integer|min:0',
            'delay'               => 'required|integer|min:0',
            'show_firstpart_only' => 'required|boolean',
            'show_timestamp'      => 'required|boolean',
            'show_country'        => 'required|boolean',
            'latest'              => 'required|boolean',
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
}
