<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Facades\App\Services\Util;
use Facades\App\Modules\Icon\Services\IconService;
use App\Enums\IconLibraryEnum;
use App\FormVariantType;
use App\FormQuestion;
use App\Modules\Form\Models\Extensions\FormVariantExtensions;
use DB;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormVariant extends Model
{
    use FormVariantExtensions;
    use HasFactory;
    use SoftDeletes;

    /**
     * automatic eager load
     * @var array
     */
    public $with = ['formVariantType'];

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'form_variant_type_id',
        'form_id',
        'choice_formula',
        'calculator_field_name',
    ];
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function steps()
    {
        return $this->hasMany('App\FormStep');
    }

    public function formSteps()
    {
        return $this->hasMany('App\FormStep', 'form_variant_id');
    }

    public function formLeads()
    {
        return $this->hasMany('App\FormLead');
    }

    public function formVariantType()
    {
        return $this->belongsTo('App\FormVariantType');
    }

    public function formVisits()
    {
        return $this->hasMany('App\FormVisit');
    }

    public function formHiddenFields()
    {
        return $this->hasMany('App\FormHiddenField');
    }

    public function webhooks()
    {
        return $this->hasMany('App\FormWebhook');
    }

    public function partialsCount()
    {
        return $this->formLeads()
        ->where('is_partial', true)
        ->count();
    }

    public function experimentPartialsCount($experimentId)
    {
        return $this->formLeads()
        ->where('form_experiment_id', $experimentId)
        ->where('is_partial', true)
        ->count();
    }

    public function buildState()
    {
        $steps = $this->formSteps()->orderBy('number')->get();

        $state = [
            'id' => $this->id,
            'deleted_at' => $this->deleted_at,
            'form_id' => $this->form->id,
            'key' => $this->form->key,
            'formTitle' => $this->title,
            'lastStepId' => count($steps),
            'lastQuestionId' => 0,
            'lastElementId' => 0,
            'steps' => [],
            'validate' => true,
            'choiceFormula' => $this->choice_formula,
            'calculator' => [
                'fieldName' => $this->calculator_field_name
            ],
            'pro' => !empty($this->form->createdBy->getSubscription()),
            'svgIcons' => [
                'fas fa-check-circle' => IconService::getSvgIcon('fas fa-check-circle') ?? ''
            ],
            'branding' => [
                'show' => $this->form->showBranding(),
                'url' => config('leadgen.branding.url'),
                'title' => config('leadgen.branding.title'),
                'prefix' => config('leadgen.branding.prefix')
            ],
        ];

        foreach ($steps as $step) {
            $currentStep = $step->buildState();
            foreach ($currentStep['questions'] as $question) {
                $faIcons = FormQuestion::getSvgIcons($question);
                foreach ($faIcons as $iconName => $iconValue) {
                    $state['svgIcons'][$iconName] = $iconValue;
                }

                if ($state['lastQuestionId'] < $question['id']) {
                    $state['lastQuestionId'] = $question['id'];
                }
            }
            foreach ($currentStep['elements'] as $element) {
                if ($state['lastElementId'] < $element['id']) {
                    $state['lastElementId'] = $element['id'];
                }
            }
            array_push($state['steps'], $currentStep);
        }

        return $state;
    }

    public function isChampion()
    {
        return $this->form_variant_type_id === $this->formVariantType->champion()->id;
    }

    public function isChallenger()
    {
        return $this->form_variant_type_id === $this->formVariantType->challenger()->id;
    }

    public function visitorCount()
    {
        $subQuery = DB::table('form_visits')
            ->select('form_visits.visitor_id')
            ->where('form_visits.form_variant_id', $this->id)
            ->groupBy('form_visits.visitor_id');

        $query = DB::table(DB::raw("({$subQuery->toSql()}) as form_visits_1"))
            ->mergeBindings($subQuery)
            ->select(DB::raw('COUNT(*) as visitors'));

        return $query->get()->first()->visitors;
    }

    public function conversionCount()
    {
        $subQuery1 = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->where('form_leads.is_partial', false)
            ->where('form_leads.form_variant_id', $this->id)
            ->whereNull('form_leads.deleted_at')
            ->select('form_visits.visitor_id', 'form_leads.form_id')
            ->groupBy('form_visits.visitor_id', 'form_leads.form_id');

        $subQuery2 = DB::table(DB::raw("({$subQuery1->toSql()}) as leads1"))
            ->mergeBindings($subQuery1)
            ->select('leads1.form_id')
            ->groupBy('leads1.visitor_id');

        $query = DB::table(DB::raw("({$subQuery2->toSql()}) as leads2"))
            ->mergeBindings($subQuery2)
            ->select(
                'leads2.form_id',
                DB::raw('COUNT(*) as conversions')
            );

        return $query->get()->first()->conversions;
    }

    public function experimentConversionCount($experimentId)
    {
        $subQuery1 = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->where('form_leads.is_partial', false)
            ->where('form_leads.form_variant_id', $this->id)
            ->where('form_visits.form_experiment_id', $experimentId)
            ->whereNull('form_leads.deleted_at')
            ->select('form_visits.visitor_id', 'form_leads.form_id')
            ->groupBy('form_visits.visitor_id', 'form_leads.form_id');

        $subQuery2 = DB::table(DB::raw("({$subQuery1->toSql()}) as leads1"))
            ->mergeBindings($subQuery1)
            ->select('leads1.form_id')
            ->groupBy('leads1.visitor_id');

        $query = DB::table(DB::raw("({$subQuery2->toSql()}) as leads2"))
            ->mergeBindings($subQuery2)
            ->select(
                'leads2.form_id',
                DB::raw('COUNT(*) as conversions')
            );

        return $query->get()->first()->conversions;
    }


    public function setting()
    {
        if (empty($this->id)) {
            return $this->hasOne('App\FormVariantSetting');
        }

        $this->hasOne('App\FormVariantSetting')->firstOrCreate([
            'form_variant_id' => $this->id
        ], [
            'auto_navigation' => false,
        ]);

        return $this->hasOne('App\FormVariantSetting');
    }

    public function duplicateWithStepsAndQuestionsAndElements(
        FormVariantType $formVariantType
    ) {
        try {
            DB::beginTransaction();

            $duplicateVariant = $this->replicate();
            $duplicateVariant->title = $this->title . ' Duplicate';
            $duplicateVariant->form_variant_type_id = $formVariantType->id;
            $duplicateVariant->save();

            foreach ($this->formSteps as $formStep) {
                $step = $formStep->replicate();
                $step->form_id = $this->form->id;
                $step->form_variant_id = $duplicateVariant->id;
                $step->save();
                foreach ($formStep->formQuestions as $formQuestion) {
                    $question = $formQuestion->replicate();
                    $question->form_step_id = $step->id;
                    $question->save();
                }
                foreach ($formStep->formElements as $formElement) {
                    $element = $formElement->replicate();
                    $element->form_step_id = $step->id;
                    $element->save();
                }
            }

            foreach ($this->formHiddenFields as $variantHiddenField) {
                $hiddenField = $variantHiddenField->replicate();
                $hiddenField->form_id = $this->form->id;
                $hiddenField->form_variant_id = $duplicateVariant->id;
                $hiddenField->save();
            }

            // variant specific webhook
            foreach ($this->webhooks as $variantWebhook) {
                $webhook = $variantWebhook->replicate();
                $webhook->form_id = $this->form->id;
                $webhook->form_variant_id = $duplicateVariant->id;
                $webhook->save();
            }

            $variantTheme = $this->formVariantTheme->replicate();
            $variantTheme->form_variant_id = $duplicateVariant->id;
            $variantTheme->save();

            DB::commit();

            $duplicateVariant->load('formVariantType');

            return $duplicateVariant;
        } catch (\Exception $e) {
            DB::rollBack();

            Util::logException($e);
        }

        return false;
    }

    public function formVariantTheme()
    {
        if (empty($this->id)) {
            return $this->hasOne('App\FormVariantTheme');
        }

        $themeDefaults = Util::themeDefault();

        $this->hasOne('App\FormVariantTheme')->firstOrCreate([
            'form_variant_id' => $this->id
        ], [
            'general' => json_encode($themeDefaults['general']),
            'typography' => json_encode($themeDefaults['typography']),
            'ui_elements' => json_encode($themeDefaults['ui_elements']),
            'custom_css' => $themeDefaults['custom_css'],
        ]);

        return $this->hasOne('App\FormVariantTheme');
    }

    /**
     * Get leadproofs of form variant.
     *
     * @return HasMany
     */
    public function leadProofs(): HasMany
    {
        return $this->hasMany(LeadProof::class, 'form_variant_id', 'id');
    }
}
