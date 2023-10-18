<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\QuestionTypesEnum;

class FormStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'form_id',
        'form_variant_id',
        'jump',
        'auto_navigation',
    ];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function formQuestions()
    {
        return $this->hasMany('App\FormQuestion');
    }

    public function formElements()
    {
        return $this->hasMany('App\FormStepElement');
    }

    public function buildState()
    {
        $state = [
            'id' => (int) $this->number,
            'dbId' => $this->id,
            'autoNavigation' => $this->auto_navigation,
            'questions' => [],
            'elements' => []
        ];

        if (!empty($this->jump)) {
            $state['jump'] = json_decode($this->jump, true);
            $state['jump']['step'] = intval($state['jump']['step']);
        }

        $questions = $this->formQuestions()->orderBy('number')->get();
        foreach ($questions as $question) {
            if (
                $question->formQuestionType->type === QuestionTypesEnum::GDPR &&
                (empty($question->config['enabled']) || $question->config['enabled'] === 'false')
            ) {
                continue;
            }

            array_push($state['questions'], $question->buildState());
        }

        $elements = $this->formElements()->orderBy('number')->get();
        foreach ($elements as $element) {
            array_push($state['elements'], $element->buildState());
        }

        return $state;
    }

    public function getState(array $step = []): array
    {
        if (empty($step)) {
            $step;
        }

        $state = [
            'id' => (int) $step['number'],
            'dbId' => $step['id'],
            'autoNavigation' => $step['auto_navigation'],
            'questions' => [],
            'elements' => []
        ];

        if (!empty($step['jump'])) {
            $state['jump'] = json_decode($step['jump'], true);
            $state['jump']['step'] = intval($state['jump']['step']);
        }

        $questions = $step['questions'];

        array_multisort(
            array_column($questions, 'number'),
            SORT_ASC,
            $questions
        );

        foreach ($questions as $question) {
            $question['config'] = json_decode($question['config'], true);

            if (
                $question['config']['type'] === QuestionTypesEnum::GDPR &&
                (empty($question['config']['enabled']) || $question['config']['enabled'] === 'false')
            ) {
                continue;
            }

            array_push($state['questions'], FormQuestion::make()->getState($question, $step));
        }

        $elements = $step['elements'];

        array_multisort(
            array_column($elements, 'number'),
            SORT_ASC,
            $elements
        );

        foreach ($elements as $element) {
            array_push($state['elements'], FormStepElement::make()->getState($element, $step));
        }

        return $state;
    }
}
