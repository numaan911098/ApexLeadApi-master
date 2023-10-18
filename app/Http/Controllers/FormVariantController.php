<?php

namespace App\Http\Controllers;

use App\Events\FormVariantCreated;
use App\Models\Credential;
use Illuminate\Http\Request;
use Facades\App\Services\Util;
use App\Http\Requests\StoreForm;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\QuestionTypesEnum;
use App\FormVariant;
use App\FormStep;
use App\FormQuestion;
use App\FormQuestionType;
use App\FormVariantType;
use App\Visitor;
use App\FormVisit;
use App\Form;
use App\FormStepElement;
use DB;
use Log;
use App\Events\FormVariantUpdated;
use Sentry;

class FormVariantController extends Controller
{
    protected $formVariantModel;
    protected $formModel;
    protected $formStepModel;
    protected $formQuestionModel;
    protected $formQuestionTypeModel;
    protected $formVariantTypeModel;
    protected $formVisitModel;
    protected $visitorModel;

    public function __construct(
        FormVariant $formVariant,
        Form $formModel,
        FormStep $formStep,
        FormQuestion $formQuestion,
        FormQuestionType $formQuestionType,
        FormVariantType $formVariantType,
        FormVisit $formVisit,
        Visitor $visitor
    ) {

        $this->middleware('jwt.auth')->except(['preview']);
        $this->formVariantModel = $formVariant;
        $this->formModel = $formModel;
        $this->formStepModel = $formStep;
        $this->formQuestionModel = $formQuestion;
        $this->formQuestionTypeModel = $formQuestionType;
        $this->formVariantTypeModel = $formVariantType;
        $this->formVisitModel = $formVisit;
        $this->visitorModel = $visitor;
    }

    public function index(Form $form, Request $request)
    {
        $this->authorize('view', $form);
        $trashed = $request->input('trashed');
        if ($trashed == 1) {
            $variants = $form->variants()->withTrashed()->whereIn('id', function ($query) {
                $query->select('form_variant_id')->from('form_leads');
            })->get();
        } else {
            $variants = $form->variants()->get();
        }
        $includes = [];
        if (!empty($_GET['with'])) {
            $includes = explode(',', $_GET['with']);
        }


        foreach ($variants as &$variant) {
            $variant->conversions = $variant->conversionCount();
            $variant->visitors_count = $variant->visitorCount();
            $variant->partials_count = $variant->partialsCount();
            if (empty($includes)) {
                continue;
            }

            foreach ($includes as $include) {
                $variant->$include = $variant->$include;
            }
        }

        return $this->apiResponse(200, $variants->toArray());
    }

    public function show($formId, $formVariantId)
    {
        $form = $this->formModel->findOrFail($formId);
        $this->authorize('view', $form);
        $formVariant = $this->formVariantModel->findOrFail($formVariantId);
        return response()->json([
            'data' => $formVariant->buildState()
        ]);
    }

    public function preview(Request $request, $key, FormVariant $variant)
    {
        $form = $this->formModel->where('key', $key)->firstOrFail();

        try {
            DB::beginTransaction();
            if ($request->filled('leadgen_visitor_id')) {
                $visitor = $this->visitorModel
                ->where('ref_id', $request->input('leadgen_visitor_id'))
                ->firstOrFail();
            } else {
                $visitor = $this->visitorModel->create([
                    'ref_id' => Util::uuid4()
                ]);
            }

            $geolocation = Util::geolocation($request->ip());

            $visit = $this->formVisitModel->createFromParams(
                $request,
                $form,
                $variant,
                $visitor,
                $geolocation,
                true
            );

            $data = $variant->getGeneratorState();
            $data['visitorId'] = $visitor->ref_id;
            $data['visitId'] = $visit->id;

            if (is_array($geolocation)) {
                $data['geolocation'] = $geolocation;
            }

            DB::commit();
            return $this->apiResponse(200, $data);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            DB::rollBack();
            Util::logException($e);
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_FETCH_ERROR,
                'Unable to create form variant, please try again.'
            );
        }
    }

    public function store(StoreForm $request, $formId)
    {
        $form = $this->formModel->findOrFail($formId);
        $this->authorize('view', $form);
        try {
            DB::beginTransaction();
            $form->save();
            $challengerVariantType = $this->formVariantTypeModel->challenger();
            $formVariant = $this->formVariantModel->create([
                'form_variant_type_id' => $challengerVariantType->id,
                'title' => $request->input('formTitle'),
                'form_id' => $form->id,
                'choice_formula' => $request->input('choiceFormula'),
                'calculator_field_name' => $request->input('calculator')['fieldName'],
            ]);
            // save steps
            $stepCount = count($request->input('steps'));
            $stepIndex = 1;
            foreach ($request->input('steps') as $step) {
                $canAutoNavigate = $step['autoNavigation'] === 'true' || $step['autoNavigation'] === '1';
                $formStep = $this->formStepModel->create([
                    'number' => $stepIndex,
                    'form_id' => $form->id,
                    'form_variant_id' => $formVariant->id,
                    'jump' => !empty($step['jump']) ? json_encode($step['jump']) : null,
                    'auto_navigation' => $canAutoNavigate,
                ]);
                // save questions
                if (!empty($step['questions'])) {
                    $questionCount = count($step['questions']);
                    $questionIndex = 1;
                    foreach ($step['questions'] as $question) {
                        $questionType = $this->formQuestionTypeModel
                            ->where('type', $question['type'])
                            ->first();
                        if ($questionType->type !== QuestionTypesEnum::SINGLE_CHOICE) {
                            $canAutoNavigate = false;
                        }

                        $question['stepId'] = $formStep->number;
                        $formQuestion = $this->formQuestionModel->create([
                            'number' => $question['number'],
                            'form_step_id' => $formStep->id,
                            'form_question_type_id' => $questionType->id,
                            'config' => json_encode($question)
                        ]);

                        $questionIndex++;
                    }
                }

                // save elements
                if (!empty($step['elements'])) {
                    foreach ($step['elements'] as $element) {
                        $element['stepId'] = $formStep->number;
                        FormStepElement::create([
                            'number' => $element['number'],
                            'type' => $element['type'],
                            'config' => json_encode($element),
                            'form_step_id' => $formStep->id
                        ]);
                    }
                }

                $formStep->auto_navigation = $canAutoNavigate;
                $formStep->save();
                $stepIndex++;
            }

            DB::commit();

            event(new FormVariantCreated($formVariant, $form));
            return $this->apiResponse(201, $formVariant->toArray());
        } catch (\Exception $e) {
            DB::rollBack();
            Util::logException($e);
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_CREATE_ERROR,
                'Unable to create form variant, please try again.'
            );
        }
    }

    public function update(StoreForm $request, $formId, $formVariantId)
    {
        $form = $this->formModel->findOrFail($formId);
        $this->authorize('view', $form);
        $formVariant = $this->formVariantModel->findOrFail($formVariantId);
        try {
            DB::beginTransaction();
            $formVariant->touch();
            $formVariant->title = $request->input('formTitle');
            $formVariant->choice_formula = $request->input('choiceFormula');
            $formVariant->calculator_field_name = $request->input('calculator')['fieldName'];
            $formVariant->save();
            $existingStepIds = [];
            $existingQuestionIds = [];
            $existingElementIds = [];
            $stepCount = count($request->input('steps'));
            $stepIndex = 1;
            foreach ($request->input('steps') as $step) {
                $canAutoNavigate = $step['autoNavigation'] === 'true' || $step['autoNavigation'] === '1';
                if (!empty($step['jump'])) {
                    $stepJump = intval($step['jump']['step']) === $stepIndex ? null : json_encode($step['jump']);
                } else {
                    $stepJump = null;
                }

                if (empty($step['dbId'])) {
                    $formStep = $this->formStepModel->create([
                        'number' => $stepIndex,
                        'form_id' => $form->id,
                        'form_variant_id' => $formVariant->id,
                        'jump' => $stepJump,
                        'auto_navigation' => $canAutoNavigate,
                    ]);
                } else {
                    $stepDbId = $step['dbId'];
                    $formStep = $this->formStepModel->find($stepDbId);
                    $formStep->number = $stepIndex;
                    if (!empty($step['jump'])) {
                        $formStep->jump =
                        intval($step['jump']['step']) === $stepIndex ? null : json_encode($step['jump']);
                    } else {
                        $formStep->jump = null;
                    }
                    $formStep->auto_navigation = $request->input('autoNavigation');
                    $formStep->save();
                }

                array_push($existingStepIds, $formStep->id);
                if (!empty($step['questions'])) {
                    $questionCount = count($step['questions']);
                    $questionIndex = 1;
                    foreach ($step['questions'] as $question) {
                        $questionType = $this->formQuestionTypeModel
                            ->where('type', $question['type'])
                            ->first();
                        if (
                            $questionType->type !== QuestionTypesEnum::SINGLE_CHOICE ||
                            $stepIndex === $stepCount
                        ) {
                            $canAutoNavigate = false;
                        }

                        // create new question if question type is changed.
                        $questionTypeChanged = false;
                        if (!empty($question['dbId'])) {
                            $formQuestion = $this->formQuestionModel->find($question['dbId']);
                            if ($formQuestion) {
                                $questionTypeChanged = $formQuestion->formQuestionType->type !== $question['type'];
                            }
                        }

                        $question['stepId'] = $formStep->number;
                        if (empty($question['dbId']) || $questionTypeChanged) {
                            $formQuestion = $this->formQuestionModel->create([
                                'number' => $question['number'],
                                'form_step_id' => $formStep->id,
                                'form_question_type_id' => $questionType->id,
                                'config' => json_encode($question)
                            ]);
                        } else {
                            $questionDbId = $question['dbId'];
                            $formQuestion = $this->formQuestionModel->find($questionDbId);
                            $formQuestion->form_step_id = $formStep->id;
                            $formQuestion->form_question_type_id = $questionType->id;
                            $formQuestion->number = $question['number'];
                            $formQuestion->config = json_encode($question);
                            $formQuestion->save();
                        }
                        array_push($existingQuestionIds, $formQuestion->id);
                        $questionIndex++;
                    }
                }


                if (!empty($step['elements'])) {
                    foreach ($step['elements'] as $element) {
                        $element['stepId'] = $formStep->number;
                        if (empty($element['dbId'])) {
                            $formElement = FormStepElement::create([
                                'number' => $element['number'],
                                'type' => $element['type'],
                                'form_step_id' => $formStep->id,
                                'config' => json_encode($element)
                            ]);
                        } else {
                            $formElement = FormStepElement::find($element['dbId']);
                            $formElement->number = $element['number'];
                            $formElement->config = json_encode($element);
                            $formElement->save();
                        }
                        array_push($existingElementIds, $formElement->id);
                    }
                }

                $formStep->auto_navigation = $canAutoNavigate;
                $formStep->save();
                $stepIndex++;
            }

            //delete remaining steps, questions and elements
            foreach ($formVariant->formSteps as $formStep) {
                if (!in_array($formStep->id, $existingStepIds)) {
                    $formStep->delete();
                    continue;
                }

                foreach ($formStep->formQuestions as $formQuestion) {
                    if (!in_array($formQuestion->id, $existingQuestionIds)) {
                        $formQuestion->delete();
                    }
                }

                foreach ($formStep->formElements as $formElement) {
                    if (!in_array($formElement->id, $existingElementIds)) {
                        $formElement->delete();
                    }
                }
            }

            DB::commit();
            event(new FormVariantUpdated($formVariant, $form));
            return $this->apiResponse(200, $formVariant->buildState());
        } catch (\Exception $e) {
            DB::rollBack();
            Util::logException($e);
            Sentry\captureException($e);
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'Unable to save form variant, please try again.'
            );
        }
    }

    public function duplicate(Form $form, FormVariant $variant)
    {

        $this->authorize('view', $form);
        $challenger = $this->formVariantTypeModel->challenger();
        $duplicateVariant = $variant->duplicateWithStepsAndQuestionsAndElements($challenger);
        if ($duplicateVariant) {
            return $this->apiResponse(200, $duplicateVariant->toArray());
        }

        return $this->apiResponse(400, [], ErrorTypes::RESOURCE_COPY_ERROR, "Unable to duplicate form variant");
    }
    public function delete(Form $form, FormVariant $variant)
    {
        $this->authorize('delete', $form);
        $variant->delete();

        return $this->apiResponse(200, $variant->toArray());
    }

    public function promote(Form $form, FormVariant $variant)
    {
        $this->authorize('view', $form);
        if ($variant->isChampion()) {
            return $this->apiResponse(200);
        }

        if ($form->current_experiment_id) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'You must stop the running experiment.'
            );
        }

        try {
            DB::beginTransaction();
            $champion = $form->championVariant();
            $championVariantTypeId = $champion->form_variant_type_id;
            $champion->form_variant_type_id = $variant->form_variant_type_id;
            $champion->save();
            $variant->form_variant_type_id = $championVariantTypeId;
            $variant->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Util::logException($e);
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'Unable to promote variant as champion. please try again.'
            );
        }

        return $this->apiResponse(200);
    }

    public function setting(Form $form, FormVariant $variant)
    {
        $this->authorize('view', $form);
        return $this->apiResponse(200, $variant->setting());
    }

    public function saveSetting(Request $request, Form $form, FormVariant $variant)
    {
        $this->authorize('update', $form);
        $variant->setting->update($request->all());
        return $this->apiResponse(200, $variant->setting->toArray());
    }
}
