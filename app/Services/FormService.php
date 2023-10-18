<?php

namespace App\Services;

use App\Form;
use App\FormVariantType;
use App\FormStepElement;
use App\Enums\QuestionTypesEnum;
use Log;

class FormService
{

    public function createFormVariant(
        Form $form,
        FormVariantType $formVariantType,
        array $data
    ) {
        $formVariant = $form->variants()->firstOrCreate([
            'form_variant_type_id' => $formVariantType->id,
            'title' => $data['formTitle'],
            'form_id' => $form->id,
            'choice_formula' => $data['choiceFormula']
        ]);
        $this->insertSteps($formVariant, $data['steps']);
    }

    public function insertSteps(FormVariant $variant, array $steps)
    {
        $stepCount = count($data['steps']);
        $stepIndex = 1;

        foreach ($data['steps'] as $step) {
            $formStep = $this->formStepModel->create([
                'number' => $stepIndex,
                'form_id' => $form->id,
                'form_variant_id' => $formVariant->id,
                'jump' => !empty($step['jump']) ? json_encode($step['jump']) : null
            ]);


            // insert step questions
            $questionCount = count($step['questions']);
            $questionIndex = 1;
            $questionOrders = [];

            foreach ($step['questions'] as $question) {
                $questionType = $this->formQuestionTypeModel
                ->where('type', $question['type'])
                ->first();

                // GDPR can exist as a last question of last step
                if ($questionType->type === QuestionTypesEnum::GDPR) {
                    if (
                        !($stepIndex === $stepCount &&
                        $questionIndex === $questionCount)
                    ) {
                        $questionIndex++;
                        continue;
                    }
                }

                $question['stepId'] = $formStep->number;
                $formQuestion = $this->formQuestionModel->create([
                    'number' => $question['order'],
                    'form_step_id' => $formStep->id,
                    'form_question_type_id' => $questionType->id,
                    'config' => json_encode($question)
                ]);

                $questionOrders[] = $question['order'];

                $questionIndex++;
            }

            //insert step elements
            foreach ($step['elements'] as $element) {
                // if (in_array($element['order'])) {
                //     return 'invalid_element_order';
                // }
                FormStepElement::create([
                    'number' => $element['order'],
                    'type' => $element['type'],
                    'config' => json_encode($element),
                    'form_step_id' => $step['id']
                ]);
            }

            $stepIndex++;
        }
    }

    public function insertStepQuestions(array $questions)
    {
    }

    public function insertStepElements(array $elements)
    {
    }
}
