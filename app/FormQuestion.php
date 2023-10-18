<?php

namespace App;

use App\Enums\FormBuilder\AddressQuestionAutocompleteApiKeySourceEnum;
use App\Enums\FormBuilder\AddressQuestionAutocompleteModeEnum;
use App\Enums\FormBuilder\AddressQuestionSkinsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Facades\App\Services\Util;
use Facades\App\Modules\Icon\Services\IconService;
use App\Models\Credential;
use App\Enums\QuestionTypesEnum;
use App\Enums\IconLibraryEnum;
use Illuminate\Support\Facades\Log;
use Arr;

class FormQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'config',
        'number',
        'form_question_type_id',
        'form_step_id'
    ];

    /**
     * The step that question belongs to.
     */
    public function formStep()
    {
        return $this->belongsTo('App\FormStep');
    }

    /**
     * The question type that question belongs to.
     */
    public function formQuestionType()
    {
        return $this->belongsTo('App\FormQuestionType');
    }

    public function buildState()
    {
        $config = $this->config;
        $config['dbId'] = $this->id;
        $config['id'] = (int) $config['id'];
        $config['stepId'] = (int) $this->formStep->number;
        $config['valid'] = empty($config['valid']) ? true : ($config['valid'] === 'true');
        $config['number'] = (int) $this->number;
        $config['hide_title'] = empty($config['hide_title']) ? false : ($config['hide_title'] === 'true');

        if (!isset($config['required'])) {
            $config['required'] = false;
        } else {
            $config['required'] = $config['required'] === 'true';
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::GDPR) {
            $config['enabled'] = (empty($config['enabled']) ? false : $config['enabled'] === 'true');
            $updatedChoices = [];

            $choiceId = 1;
            foreach ($config['options']['choices'] as $choice) {
                if (empty($choice['id'])) {
                    $choice['id'] = $choiceId++;
                } else {
                    $choice['id'] = (int) $choice['id'];
                }

                $choice['required'] = ($choice['required'] === 'true');
                $updatedChoices[] = $choice;
            }

            $config['options']['choices'] = $updatedChoices;
        }

        // make choice text compatible to new format
        if (!empty($config['choices'])) {
            $this->makeChoicesCompatible($config['choices']);

            array_multisort(
                array_column($config['choices'], 'order'),
                SORT_ASC,
                $config['choices']
            );
        }

        if (
            $this->formQuestionType->type === QuestionTypesEnum::SINGLE_CHOICE ||
            $this->formQuestionType->type === QuestionTypesEnum::MULTIPLE_CHOICE
        ) {
            $updatedJumps = [];
            if (!empty($config['jumps'])) {
                foreach ($config['jumps'] as $jump) {
                    $jump['step'] = intval($jump['step']);
                    $this->makeJumpCompatible($jump, $config['choices']);
                    $updatedJumps[] = $jump;
                }
                $config['jumps'] = $updatedJumps;
            }

            if (!empty($config['enableChoicesValues'])) {
                $config['enableChoicesValues'] = $config['enableChoicesValues'] === 'true';
            }
            if (!empty($config['enablePreSelectChoices'])) {
                $config['enablePreSelectChoices'] = $config['enablePreSelectChoices'] === 'true';
            }

            if (!empty($config['randomChoiceOrder'])) {
                $config['randomChoiceOrder'] = $config['randomChoiceOrder'] === 'true';
            } else {
                $config['randomChoiceOrder'] = false;
            }
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::DATE) {
            if (!isset($config['skin']) || empty($config['skin'])) {
                $config['skin'] = [
                    'id' => 'datePicker',
                    'label' => 'Date Picker',
                ];
            }
            if (!empty($config['enableMinMax'])) {
                $config['enableMinMax'] = $config['enableMinMax'] === 'true';
            }

            if (!empty($config['autoIncrement'])) {
                $config['autoIncrement'] = $config['autoIncrement'] === 'true';
            }
        }
        if ($this->formQuestionType->type === QuestionTypesEnum::NUMBER) {
            if (!empty($config['enableMinMaxLimit'])) {
                $config['enableMinMaxLimit'] = $config['enableMinMaxLimit'] === 'true';
            }

            if (isset($config['minNumber'])) {
                $config['minNumber'] = (int) ($config['minNumber']);
            } else {
                $config['minNumber'] = null;
            }
            if (!empty($config['maxNumber'])) {
                $config['maxNumber'] = (int)  $config['maxNumber'];
            } else {
                $config['maxNumber'] = null;
            }
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::RANGE) {
            if (!empty($config['enableUnitValues'])) {
                $config['enableUnitValues'] = $config['enableUnitValues'] === 'true';
            }
            if (!empty($config['enableStepCount'])) {
                $config['enableStepCount'] = $config['enableStepCount'] === 'true';
            }
            if (!empty($config['enableCustomText'])) {
                $config['enableCustomText'] = $config['enableCustomText'] === 'true';
            }
            if (!empty($config['showHideOrientationScale'])) {
                $config['showHideOrientationScale'] = $config['showHideOrientationScale'] === 'true';
            } else {
                $config['showHideOrientationScale'] = false;
            }
            if (!empty($config['rangeFields']['valueMin'])) {
                $config['rangeFields']['valueMin'] = (int)  $config['rangeFields']['minScaleValue'];
            } else {
                $config['rangeFields']['valueMin'] =  $config['rangeFields']['minScaleValue'];
            }
            if (isset($config['rangeFields']['valueMax'])) {
                $config['rangeFields']['valueMax'] = (int) $config['rangeFields']['maxScaleValue'];
            } else {
                $config['rangeFields']['valueMax'] =  $config['rangeFields']['maxScaleValue'];
            }
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::MULTIPLE_CHOICE) {
            if (!empty($config['enableMinMaxChoices'])) {
                $config['enableMinMaxChoices'] = $config['enableMinMaxChoices'] === 'true';
                if (!empty($config['maxChoices'])) {
                    $config['maxChoices'] = (int)  $config['maxChoices'];
                } else {
                    $config['maxChoices'] = 1;
                }
                if (!empty($config['minChoices'])) {
                    $config['minChoices'] = (int)  $config['minChoices'];
                } else {
                    $config['minChoices'] = 1;
                }
            }
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::ADDRESS) {
            // cast boolean fields
            foreach ($config['fields'] as $fieldIndex => $fieldValue) {
                foreach ($fieldValue as $fieldItemIndex => $fieldItemValue) {
                    if (
                        $fieldItemIndex === 'required' ||
                        $fieldItemIndex === 'enabled' ||
                        $fieldItemIndex === 'webhook' ||
                        $fieldItemIndex === 'auto_complete'
                    ) {
                        $config['fields'][$fieldIndex][$fieldItemIndex] = $fieldItemValue === 'true';
                    }
                }

                $order = '' ;
                if (!empty($config['fields'][$fieldIndex]['order'])) {
                    $order = $config['fields'][$fieldIndex]['order'];
                }
                switch ($fieldIndex) {
                    case 'country':
                        $order = empty($order) ? 1 : (int) $order;
                        break;
                    case 'state':
                        $order = empty($order) ? 2 : (int) $order;
                        break;
                    case 'city':
                        $order = empty($order) ? 3 : (int) $order;
                        break;
                    case 'address_line_1':
                        $order = empty($order) ? 4 : (int) $order;
                        break;
                    case 'address_line_2':
                        $order = empty($order) ? 5 : (int) $order;
                        break;
                    case 'post_code':
                        $order = empty($order) ? 6 : (int) $order;
                        break;
                    case 'landmark':
                        $order = empty($order) ? 7 : (int) $order;
                        break;
                    default:
                        $order = -1;
                }
                $config['fields'][$fieldIndex]['order'] = $order;
            }

            array_multisort(
                array_column($config['fields'], 'order'),
                SORT_ASC,
                $config['fields']
            );

            if (!isset($config['skin']) || empty($config['skin'])) {
                $config['skin'] = [
                    'id' => AddressQuestionSkinsEnum::DEFAULT,
                    'label' => ucwords(strtolower(AddressQuestionSkinsEnum::DEFAULT)),
                ];
            }

            if (!isset($config['autocompleteApiKey'])) {
                $config['autocompleteApiKey'] = '';
            }

            if (!isset($config['autocompleteApiKeySource'])) {
                $config['autocompleteApiKeySource'] = '';
            }

            if (!isset($config['autocompleteFieldsEdit'])) {
                $config['autocompleteFieldsEdit'] = false;
            } else {
                $config['autocompleteFieldsEdit'] = $config['autocompleteFieldsEdit'] === 'true';
            }

            if (!isset($config['autocompleteMode'])) {
                $config['autocompleteMode'] = AddressQuestionAutocompleteModeEnum::SEARCH;
            }
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::EMAIL_ADDRESS) {
            $config['replyTo'] = isset($config['replyTo']) ? ($config['replyTo'] === 'true') : false;
            $config['restrictEmail'] = isset($config['restrictEmail']) ? ($config['restrictEmail'] === 'true') : false;
            if (!empty($config['restrictEmailFields']) || isset($config['restrictEmailFields'])) {
                foreach ($config['restrictEmailFields'] as &$item) {
                    $item['allow'] = (int) $item['allow'];
                }
            } else {
                $config['restrictEmailFields'] = [
                    [
                        'id' => 1,
                        'email' => '',
                        'order' => 1,
                        'allow' => 1
                    ]
                ];
            }
        }

        if ($this->formQuestionType->type === QuestionTypesEnum::PHONE_NUMBER) {
            if (isset($config['enableDefaultCode'])) {
                $config['enableDefaultCode'] = $config['enableDefaultCode'] === 'true';
            } else {
                $config['enableDefaultCode'] = false;
            }
        }


        return $config;
    }

    public function getState(array $question, array $step): array
    {
        $config = $question['config'];
        $config['dbId'] = $question['id'];
        $config['id'] = (int) $config['id'];
        $config['field_name'] = empty($config['field_name'])
        ? sprintf('S%s_Q%s', $step['number'], $question['number']) : $config['field_name'];
        $config['stepId'] = (int) $step['number'];
        $config['valid'] = empty($config['valid']) ? true : ($config['valid'] === 'true');
        $config['number'] = (int) $question['number'];
        $config['hide_title'] = empty($config['hide_title']) ? false : ($config['hide_title'] === 'true');

        if (!isset($config['required'])) {
            $config['required'] = false;
        } else {
            $config['required'] = $config['required'] === 'true';
        }

        if ($config['type'] === QuestionTypesEnum::GDPR) {
            $config['enabled'] = (empty($config['enabled']) ? false : $config['enabled'] === 'true');
            $updatedChoices = [];

            $choiceId = 1;
            foreach ($config['options']['choices'] as $choice) {
                if (empty($choice['id'])) {
                    $choice['id'] = $choiceId++;
                } else {
                    $choice['id'] = (int) $choice['id'];
                }

                $choice['required'] = ($choice['required'] === 'true');
                $updatedChoices[] = $choice;
            }

            $config['options']['choices'] = $updatedChoices;
        }

        // make choice text compatible to new format
        if (!empty($config['choices'])) {
            $this->makeChoicesCompatible($config['choices']);

            array_multisort(
                array_column($config['choices'], 'order'),
                SORT_ASC,
                $config['choices']
            );
        }

        if (
            $config['type'] === QuestionTypesEnum::SINGLE_CHOICE ||
            $config['type'] === QuestionTypesEnum::MULTIPLE_CHOICE
        ) {
            $updatedJumps = [];
            if (!empty($config['jumps'])) {
                foreach ($config['jumps'] as $jump) {
                    $jump['step'] = intval($jump['step']);
                    $this->makeJumpCompatible($jump, $config['choices']);
                    $updatedJumps[] = $jump;
                }
                $config['jumps'] = $updatedJumps;
            }

            if (!empty($config['enableChoicesValues'])) {
                $config['enableChoicesValues'] = $config['enableChoicesValues'] === 'true';
            }

            if (!empty($config['randomChoiceOrder'])) {
                $config['randomChoiceOrder'] = $config['randomChoiceOrder'] === 'true';
            } else {
                $config['randomChoiceOrder'] = false;
            }
        }

        if ($config['type'] === QuestionTypesEnum::DATE) {
            if (!isset($config['skin']) || empty($config['skin'])) {
                $config['skin'] = [
                    'id' => 'datePicker',
                    'label' => 'Date Picker',
                ];
            }
            if (!empty($config['enableMinMax'])) {
                $config['enableMinMax'] = $config['enableMinMax'] === 'true';
            }

            if (!empty($config['autoIncrement'])) {
                $config['autoIncrement'] = $config['autoIncrement'] === 'true';
            }
        }
        if ($config['type'] === QuestionTypesEnum::NUMBER) {
            if (!empty($config['enableMinMaxLimit'])) {
                $config['enableMinMaxLimit'] = $config['enableMinMaxLimit'] === 'true';
            }

            if (isset($config['minNumber'])) {
                $config['minNumber'] =  (int) ($config['minNumber']);
            } else {
                $config['minNumber'] = null;
            }
            if (!empty($config['maxNumber'])) {
                $config['maxNumber'] = (int)  $config['maxNumber'];
            } else {
                $config['maxNumber'] = null;
            }
        }

        if ($config['type'] === QuestionTypesEnum::MULTIPLE_CHOICE) {
            if (!empty($config['enableMinMaxChoices'])) {
                $config['enableMinMaxChoices'] = $config['enableMinMaxChoices'] === 'true';
                if (!empty($config['maxChoices'])) {
                    $config['maxChoices'] = (int)  $config['maxChoices'];
                } else {
                    $config['maxChoices'] = 1;
                }
                if (!empty($config['minChoices'])) {
                    $config['minChoices'] = (int)  $config['minChoices'];
                } else {
                    $config['minChoices'] = 1;
                }
            }
        }

        if ($config['type'] === QuestionTypesEnum::ADDRESS) {
            // cast boolean fields
            foreach ($config['fields'] as $fieldIndex => $fieldValue) {
                foreach ($fieldValue as $fieldItemIndex => $fieldItemValue) {
                    if (
                        $fieldItemIndex === 'required' ||
                        $fieldItemIndex === 'enabled' ||
                        $fieldItemIndex === 'webhook' ||
                        $fieldItemIndex === 'auto_complete'
                    ) {
                        $config['fields'][$fieldIndex][$fieldItemIndex] = $fieldItemValue === 'true';
                    }
                }

                $order = '' ;
                if (!empty($config['fields'][$fieldIndex]['order'])) {
                    $order = $config['fields'][$fieldIndex]['order'];
                }
                switch ($fieldIndex) {
                    case 'country':
                        $order = empty($order) ? 1 : (int) $order;
                        break;
                    case 'state':
                        $order = empty($order) ? 2 : (int) $order;
                        break;
                    case 'city':
                        $order = empty($order) ? 3 : (int) $order;
                        break;
                    case 'address_line_1':
                        $order = empty($order) ? 4 : (int) $order;
                        break;
                    case 'address_line_2':
                        $order = empty($order) ? 5 : (int) $order;
                        break;
                    case 'post_code':
                        $order = empty($order) ? 6 : (int) $order;
                        break;
                    case 'landmark':
                        $order = empty($order) ? 7 : (int) $order;
                        break;
                    default:
                        $order = -1;
                }
                $config['fields'][$fieldIndex]['order'] = $order;
            }

            if (!isset($config['skin']) || empty($config['skin'])) {
                $config['skin'] = [
                    'id' => AddressQuestionSkinsEnum::DEFAULT,
                    'label' => ucwords(strtolower(AddressQuestionSkinsEnum::DEFAULT)),
                ];
            }

            if (!isset($config['autocompleteApiKey'])) {
                $config['autocompleteApiKey'] = '';
            }

            if (!isset($config['autocompleteApiKeySource'])) {
                $config['autocompleteApiKeySource'] = '';
            }

            if (!isset($config['autocompleteFieldsEdit'])) {
                $config['autocompleteFieldsEdit'] = false;
            } else {
                $config['autocompleteFieldsEdit'] = $config['autocompleteFieldsEdit'] === 'true';
            }

            if (!isset($config['autocompleteMode'])) {
                $config['autocompleteMode'] = AddressQuestionAutocompleteModeEnum::SEARCH;
            }

            if (
                isset($config['skin']) &&
                isset($config['skin']['id']) &&
                $config['skin']['id'] === AddressQuestionSkinsEnum::GOOGLE_AUTOCOMPLETE
            ) {
                if (
                    $config['autocompleteApiKeySource'] === AddressQuestionAutocompleteApiKeySourceEnum::GLOBAL_API_KEY
                ) {
                    $form = Form::find($step['form_id']);
                    $credential = Credential::find($config['autocompleteApiKey']);

                    if ($credential && $credential->created_by === $form->created_by) {
                        $config['autocompleteApiKeyValue'] = $credential->config['apikey'];
                    } else {
                        $config['autocompleteApiKeyValue'] = null;
                    }
                }

                if ($config['autocompleteApiKeySource'] === AddressQuestionAutocompleteApiKeySourceEnum::CUSTOM) {
                    $config['autocompleteApiKeyValue'] = $config['autocompleteApiKey'];
                }
            }
        }

        if (
            (
                $config['type'] === QuestionTypesEnum::SINGLE_CHOICE ||
                $config['type'] === QuestionTypesEnum::MULTIPLE_CHOICE
            ) &&
            $config['randomChoiceOrder'] === true
        ) {
            $config['choices'] = Arr::shuffle($config['choices']);
        }

        if ($config['type'] === QuestionTypesEnum::PHONE_NUMBER) {
            if (isset($config['enableDefaultCode'])) {
                $config['enableDefaultCode'] = $config['enableDefaultCode'] === 'true';
            } else {
                $config['enableDefaultCode'] = false;
            }
        }

        if ($config['type'] === QuestionTypesEnum::RANGE) {
            if (!empty($config['enableUnitValues'])) {
                $config['enableUnitValues'] = $config['enableUnitValues'] === 'true';
            }
            if (!empty($config['enableStepCount'])) {
                $config['enableStepCount'] = $config['enableStepCount'] === 'true';
            }
            if (!empty($config['enableCustomText'])) {
                $config['enableCustomText'] = $config['enableCustomText'] === 'true';
            }
            if (!empty($config['showHideOrientationScale'])) {
                $config['showHideOrientationScale'] = $config['showHideOrientationScale'] === 'true';
            }
            if (!empty($config['rangeFields']['valueMin'])) {
                $config['rangeFields']['valueMin'] = (int)  $config['rangeFields']['minScaleValue'];
            } else {
                $config['rangeFields']['valueMin'] =  $config['rangeFields']['minScaleValue'];
            }
            if (isset($config['rangeFields']['valueMax'])) {
                $config['rangeFields']['valueMax'] = (int) $config['rangeFields']['maxScaleValue'];
            } else {
                $config['rangeFields']['valueMax'] =  $config['rangeFields']['maxScaleValue'];
            }
        }

        return $config;
    }

    public function questionResponses()
    {
        return $this->hasMany('App\FormQuestionResponse');
    }

    public function getConfigAttribute($value)
    {
        return json_decode($value, true);
    }

    public function name()
    {
        if (!empty($this->config['field_name'])) {
            return $this->config['field_name'];
        }

        $step   = $this->formStep;
        $sIndex = $step->number;
        $qIndex = 1;
        $formQuestions = $step->formQuestions()->orderBy('number')->get();
        foreach ($formQuestions as $question) {
            if ($question->id === $this->id) {
                return sprintf('S%s_Q%s', $sIndex, $qIndex);
            }

            $qIndex++;
        }

        return '';
    }

    private function makeChoicesCompatible(&$choices)
    {
        foreach ($choices as $cindex => &$choice) {
            if (is_string($choice)) {
                $choice = [
                    'id' => $cindex + 1,
                    'label' => $choice,
                    'order' => $cindex + 1,
                    'description' => '',
                    'image' => '',
                    'icon' => '',
                    'selected' => false
                ];
            } else {
                $choice['id'] = (int) $choice['id'];
                $choice['order'] = (int) $choice['order'];
                $choice['description'] = isset($choice['description']) ? $choice['description'] : '';
                $choice['image'] = isset($choice['image']) ? $choice['image'] : '';
                $choice['icon'] = isset($choice['icon']) ? $choice['icon'] : '';
                $choice['selected'] = !isset($choice['selected']) ? false : $choice['selected'] === 'true';
            }
        }
    }

    private function makeJumpCompatible(&$jump, &$choices)
    {
        if (empty($jump['conditions'])) {
            return;
        }

        foreach ($jump['conditions'] as &$condition) {
            if (is_string($condition['choice']) && !is_numeric($condition['choice'])) {
                foreach ($choices as $choice) {
                    if ($condition['choice'] === $choice['label']) {
                        $condition['choice'] = $choice['id'];
                        break;
                    }
                }
            } else {
                $condition['choice'] = (int) $condition['choice'];
            }
        }
    }

    public static function getSvgIcons($config)
    {
        $icons = [];
        $allowed = [
            QuestionTypesEnum::SINGLE_CHOICE,
            QuestionTypesEnum::MULTIPLE_CHOICE,
        ];

        if (!in_array($config['type'], $allowed, true)) {
            return $icons;
        }

        if (empty($config['skin']) || $config['skin']['id'] !== 'icon') {
            return $icons;
        }

        if (empty($config['choices'])) {
            return $icons;
        }

        foreach ($config['choices'] as &$choice) {
            if (empty($choice['icon'])) {
                continue;
            }

            $icons[$choice['icon']] = IconService::getSvgIcon($choice['icon']) ?? '';
        }

        return $icons;
    }
}
