<?php

namespace App\Modules\Form\Models\Extensions;

use Facades\App\Modules\Icon\Services\IconService;
use Facades\App\Services\Util;
use App\Modules\Base\Models\Extensions\CommonExtensions;
use App\Enums\ErrorTypesEnum;
use App\FormVariant;
use App\FormStep;
use App\FormQuestion;
use App\FormStepElement;
use App\FormHiddenField;
use App\FormSetting;
use App\FormVariantSetting;
use App\FormVariantTheme;
use DB;
use Log;

trait FormVariantExtensions
{
    use CommonExtensions;

    /**
     * Get variant ui state.
     *
     * @param FormVariant $formVariant Form variant instance.
     * @return array
     */
    public function getGeneratorState(FormVariant $formVariant = null): array
    {
        $formVariant = $formVariant ?? $this;

        $querySelect = $this->getAsPrefixedSelectClause([
            FormVariant::class,
            FormStep::class,
            FormQuestion::class,
            FormStepElement::class,
            FormHiddenField::class,
            FormSetting::class,
            FormVariantSetting::class,
            FormVariantTheme::class,
        ]);

        $query = DB::table('form_variants')
            ->leftJoin('form_steps', 'form_variants.id', '=', 'form_steps.form_variant_id')
            ->leftJoin('form_questions', 'form_steps.id', '=', 'form_questions.form_step_id')
            ->leftJoin('form_hidden_fields', 'form_variants.id', '=', 'form_hidden_fields.form_variant_id')
            ->leftJoin('form_step_elements', 'form_steps.id', '=', 'form_step_elements.form_step_id')
            ->leftJoin('form_settings', 'form_variants.form_id', '=', 'form_settings.form_id')
            ->leftJoin('form_variant_settings', 'form_variant_settings.form_variant_id', '=', 'form_variants.id')
            ->leftJoin('form_variant_themes', 'form_variant_themes.form_variant_id', '=', 'form_variants.id')
            ->where('form_variants.id', $formVariant->id)
            ->orderBy('form_steps.number')
            ->select($querySelect);

        $rows = $query->get()->toArray();

        $state = [
            'id' => $this->id,
            'form_id' => $this->form->id,
            'key' => $this->form->key,
            'formTitle' => $this->title,
            'lastStepId' => 0,
            'lastQuestionId' => 0,
            'lastElementId' => 0,
            'validate' => true,
            'choiceFormula' => $this->choice_formula,
            'calculator' => [
                'fieldName' => $this->calculator_field_name
            ],
            'steps' => $this->getRowsFields(
                FormStep::make(),
                $rows,
                array_keys(Util::getAsPrefixedTableColumns(FormStep::class)),
                [
                    [
                        'key' => 'questions',
                        'model' => FormQuestion::make(),
                        'modelAttributes' => array_keys(Util::getAsPrefixedTableColumns(FormQuestion::class)),
                        'condition' => 'form_step_id',
                    ],
                    [
                        'key' => 'elements',
                        'model' => FormStepElement::make(),
                        'modelAttributes' => array_keys(Util::getAsPrefixedTableColumns(FormStepElement::class)),
                        'condition' => 'form_step_id',
                    ],
                ]
            ),
            'formHiddenFields' => $this->getRowsFields(
                FormHiddenField::make(),
                $rows,
                array_keys(Util::getAsPrefixedTableColumns(FormHiddenField::class))
            ),
            'formSetting' => $this->getRowsFields(
                FormSetting::make(),
                $rows,
                array_keys(Util::getAsPrefixedTableColumns(FormSetting::class))
            ),
            'formVariantSetting' => $this->getRowsFields(
                FormVariantSetting::make(),
                $rows,
                array_keys(Util::getAsPrefixedTableColumns(FormVariantSetting::class))
            ),
            'formVariantTheme' => $this->getRowsFields(
                FormVariantTheme::make(),
                $rows,
                array_keys(Util::getAsPrefixedTableColumns(FormVariantTheme::class))
            ),
            'pro' => !empty($this->form->createdBy->getSubscription()),
            'svgIcons' => [
                'fas fa-check-circle' => IconService::getSvgIcon('fas fa-check-circle') ?? '',
                'material-icons-outlined sentiment_neutral'
                 => IconService::getSvgIcon('material-icons-outlined sentiment_neutral') ?? '',
                'material-icons-outlined sentiment_very_dissatisfied'
                => IconService::getSvgIcon('material-icons-outlined sentiment_very_dissatisfied') ?? '',
                'material-icons-outlined sentiment_satisfied_alt'
                => IconService::getSvgIcon('material-icons-outlined sentiment_satisfied_alt') ?? '',
                'material-icons sentiment_very_satisfied'
                 => IconService::getSvgIcon('material-icons sentiment_very_satisfied') ?? '',
                'material-icons sentiment_very_dissatisfied'
                 => IconService::getSvgIcon('material-icons sentiment_very_dissatisfied') ?? '',
            ],
            'branding' => [
                'show' => $this->form->showBranding(),
                'url' => config('leadgen.branding.url'),
                'title' => config('leadgen.branding.title'),
                'prefix' => config('leadgen.branding.prefix')
            ],
            'countries' => Util::getCountries(),
            'googleFonts' => Util::getGoogleFonts(),
            'errorTypes' => ErrorTypesEnum::getConstants(),
            'formTrackingEvents' => $this->form->formTrackingEvents
        ];

        if (is_array($state['formHiddenFields'])) {
            $state['formHiddenFields'] = array_values($state['formHiddenFields']);
        }


        if (is_array($state['formSetting']) && !empty($state['formSetting'])) {
            $state['formSetting'] = array_pop($state['formSetting']);
            $state['formSetting'] = FormSetting::make()->fill($state['formSetting'])->toArray();
        }

        if (is_array($state['formVariantSetting'])) {
            $state['formVariantSetting'] = array_pop($state['formVariantSetting']);
        }

        $steps = [];
        foreach ($state['steps'] as $step) {
            $state['lastStepId']++;

            $step = FormStep::make()->getState($step);

            foreach ($step['questions'] as $question) {
                $faIcons = FormQuestion::getSvgIcons($question);

                foreach ($faIcons as $iconName => $iconValue) {
                    $state['svgIcons'][$iconName] = $iconValue;
                }

                if ($state['lastQuestionId'] < $question['id']) {
                    $state['lastQuestionId'] = $question['id'];
                }
            }

            foreach ($step['elements'] as $element) {
                if ($state['lastElementId'] < $element['id']) {
                    $state['lastElementId'] = $element['id'];
                }
            }

            $steps[] = $step;
        }

        $state['steps'] = $steps;

        if (is_array($state['formVariantTheme'])) {
            $state['formVariantTheme'] = array_pop($state['formVariantTheme']);
            $formVariantTheme = FormVariantTheme::make()->fill($state['formVariantTheme']);
            $state['formVariantTheme'] = $formVariantTheme->getTheme();
            $state['svgIcons'] = array_merge(
                $state['svgIcons'],
                $formVariantTheme->getThemeSvgIcons()
            );
        }

        return $state;
    }
}
