<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\FormQuestion;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Log;
use App\Enums\QuestionTypesEnum;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;

class StoreLeadRequest extends FormRequest
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
            'key' => 'required',
            'steps' => 'required',
            'previewMode' => 'required',
            'hiddenFields' => 'array',
            'calculator_total' => 'numeric',
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
            if (!empty($this->input('steps'))) {
                if (count($this->input('steps')) === 0) {
                    $validator->errors()->add('steps', 'No steps found');
                } else {
                    $this->validateSteps($validator, $this->input('steps'));
                }
            }
        });
    }

    protected function validateSteps(Validator $validator, array $steps)
    {
        $questionsCount = 0;
        foreach ($steps as $step) {
            if (!empty($step['questions'])) {
                $questionsCount += count($step['questions']);
                $this->validateQuestions($validator, $step['questions']);
                return;
            }
        }
    }

    protected function validateQuestions(Validator $validator, array $questions)
    {
        foreach ($questions as $question) {
            if (empty($question['type'])) {
                $validator
                ->errors()
                ->add('question', 'question type field is required');
                return;
            }

            if (empty($question['dbId'])) {
                $validator
                ->errors()
                ->add('question', 'question dbId field is required');
                return;
            }

            if ($this->hasInvalidQuestionType($question)) {
                $validator
                ->errors()
                ->add('questions', 'Invalid question type ' . $question['type']);
            }

            $this->validateQuestionResponse($validator, $question);
        }
    }

    protected function hasInvalidQuestionType(array $question)
    {
        return !in_array($question['type'], QuestionTypesEnum::getConstants());
    }

    protected function validateQuestionResponse(Validator $validator, array $question)
    {
        $formQuestion = FormQuestion::find($question['dbId']);

        if (empty($formQuestion)) {
            $validator
            ->errors()
            ->add('question', 'question dbId field is invalid');
            return;
        }

        if ($formQuestion->required && empty($question['value'])) {
            $validator
            ->errors()
            ->add('question', 'question value field is required');
            return;
        }

        if (!empty($question['value'])) {
            $questionValue = $question['value'];
        } else {
            return;
        }

        if ($formQuestion->formQuestionType->type !== $question['type']) {
            $validator
                ->errors()
                ->add('question', 'question type field is invalid');
            return;
        }

        switch ($question['type']) {
            case QuestionTypesEnum::SHORT_TEXT:
                break;
            case QuestionTypesEnum::PARAGRAPH_TEXT:
                break;
            case QuestionTypesEnum::EMAIL_ADDRESS:
                if ($questionValue && !filter_var($questionValue, FILTER_VALIDATE_EMAIL)) {
                    $validator
                    ->errors()
                    ->add('question', 'email question value must be valid email address');
                }
                break;
            case QuestionTypesEnum::PHONE_NUMBER:
                break;
            case QuestionTypesEnum::SINGLE_CHOICE:
                $choices = [];
                foreach ($formQuestion->config['choices'] as $choice) {
                    if (is_array($choice)) {
                        $choices[] = $choice['label'];
                    } else {
                        $choices[] = $choice;
                    }
                }
                if (!empty($questionValue)) {
                    if (is_array($questionValue)) {
                        $questionValue = $questionValue['label'];
                    }
                }
                if (!in_array($questionValue, $choices)) {
                    $validator
                    ->errors()
                    ->add('question', 'single choice question value is invalid');
                }
                break;
            case QuestionTypesEnum::MULTIPLE_CHOICE:
                if (!is_array($questionValue)) {
                    $validator
                    ->errors()
                    ->add('question', 'multiple choice question value must be array');
                }
                break;
        }
    }
}
