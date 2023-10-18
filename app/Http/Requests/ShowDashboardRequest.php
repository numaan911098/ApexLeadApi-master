<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use App\Enums\WidgetTypesEnum;
use Auth;

class ShowDashboardRequest extends FormRequest
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
            'widget' => 'required|array',
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

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('widget')['type'] === WidgetTypesEnum::GENERAL) {
                $this->validateGeneralWidget($validator);
            }
        });
    }

    public function validateGeneralWidget(Validator $validator)
    {
        if (!isset($this->input('widget')['params'])) {
            return;
        }

        if (!isset($this->input('widget')['params']['filter_params']['forms'])) {
            return;
        }

        $formIds = $this->input('widget')['params']['filter_params']['forms']['ids'];
        if (!empty($formIds)) {
            $forms = Auth::user()
                ->forms()
                ->select(['id'])
                ->get();

            foreach ($formIds as $formId) {
                if (!$forms->contains($formId)) {
                    $validator
                        ->errors()
                        ->add(
                            'filter_params.forms.ids',
                            'Your\'re not allowd to access form with id = ' . $formId
                        );
                }
            }
        }
    }
}
