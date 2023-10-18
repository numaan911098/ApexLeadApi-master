<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandingPageOptinRequest extends FormRequest
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
            'landing_page_id' => 'required|integer',
            'landing_page_visit_id' => 'required|integer',
            'form_lead_id' => 'integer|nullable'
        ];
    }
}
