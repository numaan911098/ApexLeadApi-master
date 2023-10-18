<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Form;
use App\Enums\ErrorTypesEnum as ErrorType;
use Log;

class StoreFormExperimentRequest extends FormRequest
{

    protected $formModel;

    protected $form;

    public function __construct(Form $form)
    {
        $this->formModel = $form;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $formId = $this->input('formId');
        if (empty($formId)) {
            return false;
        }

        $this->form = $this->formModel->find($formId);
        if (empty($this->form)) {
            return false;
        }

        return $this->form->created_by = $this->user()->id;
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
            'formId' => 'required',
            'variants' => 'required|array',
            'variants.*' => 'numeric|between:0,100'
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

            $variants = $this->form->variants;
            $inputVariants = $this->input('variants');

            if (!empty($inputVariants)) {
                $fifties = 0;
                foreach ($inputVariants as $id => $weight) {
                    if ($weight == 50) {
                        $fifties++;
                    }

                    if ($weight != 50 && $weight != 0) {
                        $validator
                        ->errors()
                        ->add('variant weights', 'weights allowed only 0 and 50');
                        continue;
                    }

                    $variant = $variants->where('id', $id)->first();
                    if (empty($variant)) {
                        $validator
                        ->errors()
                        ->add('variant.' . $id, 'record not found');
                        continue;
                    }

                    if ($variant->isChampion() && $weight != 50) {
                        $validator
                        ->errors()
                        ->add('variant weights', 'Champion variant weight must be 50');
                    }
                }

                if ($fifties !== 2) {
                    $validator
                    ->errors()
                    ->add('variant weights', 'Only two variants should have weight 50 applied');
                }
            }
        });
    }
}
