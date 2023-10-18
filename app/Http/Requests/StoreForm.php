<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Enums\QuestionTypesEnum;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use App\Enums\FormStepElementsEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoreForm extends FormRequest
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
            'formTitle' => 'required',
            'steps' => 'required'
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

    protected function validateSteps($validator, array $steps)
    {
        if (!$this->hasDuplicateFirstAndLastNameQuestionType($validator, $steps)) {
            return;
        }

        if (!$this->hasDuplicateQuestionFieldName($validator, $steps)) {
            return;
        }

        $questionsCount = 0;
        foreach ($steps as $step) {
            $this->validateQuestionAndElementOrder($validator, $step);
            if (!empty($step['questions'])) {
                $questionsCount += count($step['questions']);
                $this->validateQuestions($validator, $step['questions']);
            }
            if (!empty($step['elements'])) {
                $this->validateElements($validator, $step['elements']);
            }
        }

        if ($questionsCount < 1) {
            $validator->errors()->add('questions', 'No question found. Form should have atlease one question');
        }
    }

    protected function validateQuestionAndElementOrder($validator, $step)
    {
        if (empty($step['questions']) || count($step['questions']) === 0) {
            return;
        }

        if (empty($step['elements']) || count($step['elements']) === 0) {
            return;
        }

        $questionOrders = [];
        foreach ($step['questions'] as $question) {
            $questionOrder[] = $question['number'];
        }

        foreach ($step['elements'] as $element) {
            if (in_array($element['number'], $questionOrders)) {
                $validator
                    ->errors()
                    ->add('question', 'question number value must be different than element number');
                return;
            }
        }
    }

    protected function validateElements($validator, array $elements)
    {
        $elementTypes = FormStepElementsEnum::getConstants();
        foreach ($elements as $element) {
            if (!in_array($element['type'], $elementTypes)) {
                $validator
                    ->errors()
                    ->add('element', 'element type is invalid');
                return;
            }
        }
    }

    protected function validateQuestions($validator, array $questions)
    {
        foreach ($questions as $question) {
            if (empty($question['type'])) {
                $validator
                    ->errors()
                    ->add('question', 'question type field is required');
                return;
            }

            if (empty($question['title'])) {
                $validator
                    ->errors()
                    ->add('question', 'question title field is required');
                return;
            }

            if ($this->hasInvalidQuestionType($question)) {
                $validator
                    ->errors()
                    ->add('questions', 'Invalid question type ' . $question['type']);
            }

            if ($question['type'] === QuestionTypesEnum::SINGLE_CHOICE) {
                $this->validateSingleChoiceQuestion($validator, $question);
            }

            if ($question['type'] === QuestionTypesEnum::MULTIPLE_CHOICE) {
                $this->validateMultipleChoiceQuestion($validator, $question);
            }

            if ($question['type'] === QuestionTypesEnum::GDPR) {
                $this->validateGDPRQuestion($validator, $question);
            }

            if ($question['type'] === QuestionTypesEnum::DATE) {
                $this->validateDateQuestion($validator, $question);
            }

            if ($question['type'] === QuestionTypesEnum::ADDRESS) {
                $this->validateAddressQuestion($validator, $question);
            }
        }
    }

    protected function hasInvalidQuestionType(array $question)
    {
        return !in_array($question['type'], QuestionTypesEnum::getConstants());
    }

    protected function validateSingleChoiceQuestion(Validator $validator, $question)
    {
        if (empty($question['choices'])) {
            $validator
                ->errors()
                ->add('questions', QuestionTypesEnum::SINGLE_CHOICE . ' type question choices are empty ');
            return;
        }

        $choices = $question['choices'];
        $choicesCount = count($choices);

        if ($choicesCount < 1) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' type question should have atleast one choice'
                );
            return;
        }

        $questionChoiceLabels = [];
        $questionChoiceIds = [];
        $questionChoiceOrders = [];

        foreach ($choices as $choice) {
            if (!isset($choice['label'])) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        QuestionTypesEnum::SINGLE_CHOICE . ' type question shouldn\'t have empty choice label.'
                    );
            }

            $questionChoiceLabels[] = $choice['label'];
            $questionChoiceIds[] = $choice['id'];
            $questionChoiceOrders[] = $choice['order'];
        }

        if ($choicesCount !== count(array_unique($questionChoiceLabels))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' type question shouldn\'t have duplicate choice label.'
                );
            return;
        }
        if ($choicesCount !== count(array_unique($questionChoiceIds))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' type question shouldn\'t have duplicate choice id.'
                );
            return;
        }
        if ($choicesCount !== count(array_unique($questionChoiceOrders))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' type question shouldn\'t have duplicate choice order.'
                );
            return;
        }

        if ($choicesCount !== count($question['choicesValues'])) {
            $validator->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' should have choiceValue for each choice.'
                );
        }

        if (!empty($question['skin'])) {
            $this->validateSingleSelectSkin($validator, $question['skin']);
        }

        $this->validateLogicJump($validator, $question);
    }

    protected function validateMultipleChoiceQuestion(Validator $validator, $question)
    {
        if (empty($question['choices'])) {
            $validator
                ->errors()
                ->add('questions', QuestionTypesEnum::MULTIPLE_CHOICE . ' type question choices are empty ');
            return;
        }

        $choices = $question['choices'];
        $choicesCount = count($choices);

        if ($choicesCount < 1) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' type question should have atleast one choice'
                );
            return;
        }

        $questionChoiceLabels = [];
        $questionChoiceIds = [];
        $questionChoiceOrders = [];

        foreach ($choices as $choice) {
            if (!isset($choice['label'])) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        QuestionTypesEnum::MULTIPLE_CHOICE . ' type question shouldn\'t have empty choice label.'
                    );
            }

            $questionChoiceLabels[] = $choice['label'];
            $questionChoiceIds[] = $choice['id'];
            $questionChoiceOrders[] = $choice['order'];
        }

        if ($choicesCount !== count(array_unique($questionChoiceLabels))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' type question shouldn\'t have duplicate choice label.'
                );
            return;
        }
        if ($choicesCount !== count(array_unique($questionChoiceIds))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' type question shouldn\'t have duplicate choice id.'
                );
            return;
        }
        if ($choicesCount !== count(array_unique($questionChoiceOrders))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' type question shouldn\'t have duplicate choice orders.'
                );
            return;
        }

        if ($choicesCount !== count($question['choicesValues'])) {
            $validator->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' should have choiceValue for each choice.'
                );
        }

        if (!empty($question['skin'])) {
            $this->validateMultiSelectSkin($validator, $question['skin']);
        }

        $this->validateLogicJump($validator, $question);
    }

    protected function validateGDPRQuestion(Validator $validator, $question)
    {
        if (empty($question['options'])) {
            $validator
                ->errors()
                ->add('questions', QuestionTypesEnum::GDPR . ' no choices field found');
            return;
        }

        if (empty($question['options']['choices'])) {
            $validator
                ->errors()
                ->add('questions', QuestionTypesEnum::GDPR . ' no choices field found');
            return;
        }

        if (count($question['options']['choices']) === 0) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::GDPR . ' should have atleast one choice.'
                );
            return;
        }

        $choicesArray = [];
        foreach ($question['options']['choices'] as $choice) {
            $choicesArray[] = $choice['label'];
        }
        if (count($choicesArray) !== count(array_unique($choicesArray))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::GDPR . ' type question shouldn\'t have duplicate choices'
                );
        }
    }

    protected function validateDateQuestion(Validator $validator, $question)
    {
        $minDate = empty($question['minDate']) ? false :  $question['minDate'];
        $maxDate = empty($question['maxDate']) ? false :  $question['maxDate'];
        if ($minDate) {
            try {
                Carbon::parse($minDate);
            } catch (\Exception $exp) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        QuestionTypesEnum::DATE . ' should have valid minDate'
                    );
                return;
            }
        }
        if ($maxDate) {
            try {
                Carbon::parse($maxDate);
            } catch (\Exception $exp) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        QuestionTypesEnum::DATE . ' should have valid maxDate'
                    );
                return;
            }
        }
        if ($minDate && $maxDate) {
            if (Carbon::parse($minDate)->gt(Carbon::parse($maxDate))) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        QuestionTypesEnum::DATE . ' minDate should be less than maxDate'
                    );
            }
        }
    }

    protected function validateAddressQuestion(Validator $validator, $question)
    {
        if (empty($question['fields'])) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::ADDRESS . ' should have atleast one field'
                );
        }

        $fieldsEnabled = false;

        foreach ($question['fields'] as $field) {
            if ($field['enabled'] === 'true') {
                $fieldsEnabled = true;
                break;
            }
        }

        if (!$fieldsEnabled) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::ADDRESS . ' should have atleast one field enabled'
                );
        }
    }

    protected function validateLogicJump(Validator $validator, $question)
    {
        if (empty($question['jumps'])) {
            return;
        }

        $type = $question['type'];

        // check for required fields
        foreach ($question['jumps'] as $jump) {
            if (empty($jump['step'])) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        $type . ' logic jump step field is empty'
                    );
                return;
            }

            if (empty($jump['conditions']) || count($jump['conditions']) === 0) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        $type . ' logic jump should have condition'
                    );
                return;
            }

            foreach ($jump['conditions'] as $condition) {
                if (empty($condition['choice'])) {
                    $validator
                        ->errors()
                        ->add(
                            'questions',
                            $type . ' logic jump condition choice is empty'
                        );
                    return;
                }

                if (
                    $type === QuestionTypesEnum::MULTIPLE_CHOICE &&
                    empty($condition['operator'])
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'questions',
                            $type . ' logic jump condition operator is empty'
                        );
                    return;
                }
            }
        }
    }

    protected function hasDuplicateFirstAndLastNameQuestionType(
        Validator $validator,
        array $steps
    ) {
        $firstNameTypes = 0;
        $lastNameTypes = 0;
        foreach ($steps as $step) {
            if (!empty($step['questions'])) {
                foreach ($step['questions'] as $question) {
                    $type = $question['type'];
                    if ($type === QuestionTypesEnum::FIRST_NAME) {
                        $firstNameTypes++;
                        if ($firstNameTypes > 1) {
                            $validator
                                ->errors()
                                ->add(
                                    'questions',
                                    $type . ' is only allowed once in a form.'
                                );
                            return false;
                        }
                    }
                    if ($type === QuestionTypesEnum::LAST_NAME) {
                        $lastNameTypes++;
                        if ($lastNameTypes > 1) {
                            $validator
                                ->errors()
                                ->add(
                                    'questions',
                                    $type . ' is only allowed once in a form.'
                                );
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    protected function hasDuplicateQuestionFieldName(
        Validator $validator,
        array $steps
    ) {
        $fieldNames = [];
        foreach ($steps as $step) {
            if (!empty($step['questions'])) {
                foreach ($step['questions'] as $question) {
                    if (empty($question['field_name'])) {
                        continue;
                    } else {
                        $fieldName = $question['field_name'];

                        if ($fieldName === $this->input('calculator')['fieldName']) {
                            $validator
                                ->errors()
                                ->add(
                                    'questions',
                                    'field name is used in calculator'
                                );
                            return false;
                        }
                        $fieldNames[] = $fieldName;
                    }
                }
            }
        }

        $fieldNames = array_count_values($fieldNames);
        foreach ($fieldNames as $fieldName => $fieldCount) {
            if ($fieldCount > 1) {
                $validator
                    ->errors()
                    ->add(
                        'questions',
                        'Duplicate field_name = "' . $fieldName . '" is found.'
                    );
                return false;
            }
        }

        return true;
    }

    protected function validateSingleSelectSkin(Validator $validator, $skin)
    {
        $skins = [
            'button' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'radio' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'radio_outline' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'dropdown' => [
                'alignments' => []
            ],
            'image' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'icon' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
        ];

        if (!is_array($skin)) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' skin must be an array'
                );
            return;
        }

        if (!in_array($skin['id'], array_keys($skins))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' skin id is invalid'
                );
        }

        if (
            !empty($skin['alignment']) &&
            !in_array($skin['alignment'], $skins[$skin['id']]['alignments'])
        ) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::SINGLE_CHOICE . ' skin alignment is invalid'
                );
            return;
        }
    }

    protected function validateMultiSelectSkin(Validator $validator, $skin)
    {
        $skins = [
            'button' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'checkbox' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'dropdown' => [
                'alignments' => []
            ],
            'image' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
            'icon' => [
                'alignments' => [
                    'vertical',
                    'vertical_center',
                    'horizontal',
                    'horizontal_center',
                    'horizontal_space_between',
                    'horizontal_space_around',
                ]
            ],
        ];

        if (!is_array($skin)) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' skin must be an array'
                );
            return;
        }

        if (!in_array($skin['id'], array_keys($skins))) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' skin id is invalid'
                );
        }

        if (
            !empty($skin['alignment']) &&
            !in_array($skin['alignment'], $skins[$skin['id']]['alignments'])
        ) {
            $validator
                ->errors()
                ->add(
                    'questions',
                    QuestionTypesEnum::MULTIPLE_CHOICE . ' skin alignment is invalid'
                );
            return;
        }
    }
}
