<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;

class UpdateFormSettingRequest extends FormRequest
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
            'email_notifications' => 'required|boolean',
            'enable_thankyou_url' => 'required|boolean',
            'thankyou_url' => 'nullable|url',
            'domains' => 'nullable|domain_array',
            'accept_responses' => 'required|boolean',
            'steps_summary' => 'required',
            'to' => 'comma_separated_emails',
            'cc' => 'nullable|comma_separated_emails',
            'bcc' => 'nullable|comma_separated_emails',
            'response_limit' => 'integer|max:100'
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
            if (!empty($this->input('email_notifications'))) {
                if (empty($this->input('to'))) {
                    $validator
                    ->errors()
                    ->add('to', 'To Address field is required');
                }
                if (empty($this->input('subject'))) {
                    $validator
                    ->errors()
                    ->add('subject', 'Subject field is required');
                }
            }

            if (empty($this->input('enable_thankyou_url'))) {
                if (empty($this->input('thankyou_message'))) {
                    $validator
                    ->errors()
                    ->add('thankyou_message', 'Thankyou Message field is required');
                }
            } else {
                if (empty($this->input('thankyou_url'))) {
                    $validator
                    ->errors()
                    ->add('thankyou_url', 'Thankyou Url field is required');
                }
            }


            if (!empty($this->input('accept_responses'))) {
                if (empty($this->input('response_limit'))) {
                    $validator
                    ->errors()
                    ->add('to', 'Response Limit field is required');
                }
            }
        });
    }
}
