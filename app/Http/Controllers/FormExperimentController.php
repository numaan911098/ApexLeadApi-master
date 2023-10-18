<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFormExperimentRequest;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\FormExperimentTypesEnum;
use App\Enums\ExperimentStatesEnum;
use App\Form;
use App\FormVisit;
use App\FormVariant;
use App\FormExperiment;
use App\FormExperimentType;
use App\FormExperimentVariant;
use Carbon\Carbon;
use Log;
use DB;

class FormExperimentController extends Controller
{
    protected $formModel;
    protected $formExperimentModel;
    protected $formExperimentVariantModel;
    protected $formExperimentTypeModel;
    protected $formVisitModel;
    protected $formVariantModel;

    public function __construct(
        Form $form,
        FormExperiment $formExperiment,
        FormExperimentType $formExperimentType,
        FormExperimentVariant $formExperimentVariant,
        FormVisit $formVisit,
        FormVariant $formVariant
    ) {

        $this->middleware('jwt.auth');
        $this->formModel = $form;
        $this->formExperimentModel = $formExperiment;
        $this->formExperimentVariantModel = $formExperimentVariant;
        $this->formExperimentTypeModel = $formExperimentType;
        $this->formVisitModel = $formVisit;
        $this->formVariantModel = $formVariant;
    }


    public function index(Form $form)
    {
        $this->authorize('view', $form);
        return $this->apiResponse(200, $form->formExperiments->all());
    }

    public function show(Form $form, FormExperiment $experiment)
    {
        $this->authorize('view', $form);
        return $this->apiResponse(200, $experiment->toArray());
    }

    public function store(StoreFormExperimentRequest $request, Form $form)
    {
        try {
            DB::beginTransaction();
            $formExperiment = $this->formExperimentModel->create([
                'title' => $request->input('title'),
                'form_id' => $form->id,
                'note' => $request->input('note'),
                'form_experiment_type_id' => $this->formExperimentTypeModel->ab()->id
            ]);
            foreach ($request->input('variants') as $id => $weight) {
                $this->formExperimentVariantModel->create([
                    'weight' => $weight,
                    'usage' => 0,
                    'form_experiment_id' => $formExperiment->id,
                    'form_variant_id' => $id
                ]);
            }

            DB::commit();
            return $this->apiResponse(201, $this->formExperimentModel->find($formExperiment->id)->toArray());
        } catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::info($e->getTraceAsString());
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_CREATE_ERROR,
                'Unable to save form please try again'
            );
        }
    }


    public function update(StoreFormExperimentRequest $request, Form $form, FormExperiment $experiment)
    {
        $this->authorize('update', $form);
        try {
            DB::beginTransaction();
            $experiment->title = $request->input('title');
            $experiment->note = $request->input('note');
            $experiment->save();
            foreach ($request->input('variants') as $id => $weight) {
                $experimentVariant = $this->formExperimentVariantModel
                ->where('form_experiment_id', $experiment->id)
                ->where('form_variant_id', $id)
                ->first();
                if (empty($experimentVariant)) {
                    $this->formExperimentVariantModel->create([
                        'weight' => $weight,
                        'usage' => 0,
                        'form_experiment_id' => $experiment->id,
                        'form_variant_id' => $id
                    ]);
                } else {
                    $experimentVariant->weight = $weight;
                    $experimentVariant->save();
                }
            }

            DB::commit();
            return $this->apiResponse(201, $experiment->toArray());
        } catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::info($e->getTraceAsString());
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'Unable to save form please try again'
            );
        }
    }

    public function start(Request $request, Form $form, FormExperiment $experiment)
    {
        $this->authorize('update', $form);

        if (!empty($form->current_experiment_id)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::ANOTHER_EXPERIMENT_RUNNING,
                'Stop your already running experiment first then start this one.'
            );
        }

        if ($experiment->started_at && !$experiment->ended_at) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::EXPERIMENT_ALREADY_RUNNING,
                'Experiment is already in progress'
            );
        }

        if ($experiment->started_at && $experiment->ended_at) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::EXPERIMENT_ALREADY_ENDED,
                'Experiment already ended'
            );
        }

        try {
            DB::beginTransaction();
            $experiment->started_at = Carbon::now()->toDateTimeString();
            $experiment->save();
            $form->current_experiment_id = $experiment->id;
            $form->save();
            DB::commit();
            return $this->apiResponse(200, $experiment->toArray());
        } catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::info($e->getTraceAsString());
            return $this->apiResponse(400, [], ErrorTypes::RESOURCE_UPDATE_ERROR, 'Unable to start experiment');
        }
    }

    public function end(Request $request, Form $form, FormExperiment $experiment)
    {
        $this->authorize('update', $form);
        if (!$experiment->started_at) {
            return $this->apiResponse(400, [], ErrorTypes::EXPERIMENT_NOT_STARTED, 'Experiment is not started yet');
        }

        if ($experiment->started_at && $experiment->ended_at) {
            return $this->apiResponse(400, [], ErrorTypes::EXPERIMENT_ALREADY_ENDED, 'Experiment already ended');
        }

        try {
            DB::beginTransaction();
            $experiment->ended_at = Carbon::now()->toDateTimeString();
            $experiment->save();
            $form->current_experiment_id = null;
            $form->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::info($e->getTraceAsString());
            return $this->apiResponse(400, [], ErrorTypes::RESOURCE_UPDATE_ERROR, 'Unable to end experiment');
        }

        return $this->apiResponse(200, $experiment->toArray());
    }

    public function result(Form $form, FormExperiment $experiment)
    {
        $this->authorize('view', $form);
        if ($experiment->state() === ExperimentStatesEnum::DRAFT) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::EXPERIMENT_NOT_STARTED,
                'No results found because experiment is not started yet'
            );
        }

        $formExperimentType = $experiment->formExperimentType;
        if ($formExperimentType->type === FormExperimentTypesEnum::AB) {
            $competitors = $experiment
            ->formExperimentVariants
            ->where('weight', 50);
            if ($competitors->count() > 2) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::RESOURCE_FETCH_ERROR,
                    'Unable to get experiment results'
                );
            }

            $winnerId = null;
            $highestConversionRate = 0;
            foreach ($competitors as $competitor) {
                $variant = $this->formVariantModel->find($competitor->form_variant_id);
                $visits = $variant->formVisits()
                ->where('form_experiment_id', $experiment->id);
                $competitor->visit_count = $visits->count();
                $competitor->visitor_count = $variant->visitorCount();
                $competitor->partial_count = $variant->experimentPartialsCount($experiment->id);
                $competitor->conversion_count = $variant
                ->experimentConversionCount($experiment->id);
                $competitor->lead_count = $variant
                ->formLeads
                ->where('form_experiment_id', $experiment->id)
                ->where('is_partial', false)
                ->count();
                $competitor->conversion_rate = 0;
                if ($competitor->visitor_count) {
                    $competitor->conversion_rate =
                    round($competitor->conversion_count / $competitor->visitor_count, 2);
                }

                if ($experiment->state() === ExperimentStatesEnum::RUNNING) {
                    $winnerId = null;
                } elseif ($experiment->state() === ExperimentStatesEnum::ENDED) {
                    if ($competitor->conversion_rate > $highestConversionRate) {
                        $highestConversionRate = $competitor->conversion_rate;
                        $winnerId = $variant->id;
                    }
                }

                $competitor->champion = $variant->isChampion();
            }

            $data = [
                'id' => $experiment->id,
                'competitors' => [$competitors->first(), $competitors->last()],
                'winnerCompetitorId' => $winnerId
            ];
            return $this->apiResponse(200, $data);
        }


        return $this->apiResponse(400, [], ErrorTypes::RESOURCE_FETCH_ERROR, 'Unable to get experiment results');
    }
}
