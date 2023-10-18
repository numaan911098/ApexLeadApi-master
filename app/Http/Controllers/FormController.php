<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Form;
use App\FormStep;
use App\FormStepElement;
use App\FormQuestion;
use App\FormQuestionType;
use App\FormVariantType;
use App\FormVisit;
use App\Visitor;
use App\FormLead;
use App\FormSetting;
use App\Http\Requests\StoreForm;
use App\Http\Requests\ShareForm;
use App\Http\Requests\UpdateFormSettingRequest;
use App\Models\ContactState;
use App\Models\FormPartialLead;
use App\Models\GlobalPartialLeadSetting;
use App\Enums\ConfigKeyEnum;
use App\Enums\QuestionTypesEnum;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Events\FormCreated;
use App\Services\BlacklistIpService;
use App\Services\Lists\GeneralListService;
use App\Services\Lists\FormListService;
use Facades\App\Services\Util;
use Auth;
use DB;
use Validator;
use Sentry;

class FormController extends Controller
{
    protected $formModel;

    protected $formStepModel;

    protected $formQuestionModel;

    protected $formQuestionTypeModel;

    protected $formVariantTypeModel;

    protected $formVisitModel;

    protected $contactStateModel;

    protected $formLeadModel;

    /**
     * @var FormPartialLead
     */
    protected $formPartialLeadModel;

    /**
     * @var User
     */
    protected $userModel;

    /**
     * @var GlobalPartialLeadSetting
     */
    protected $globalPartialLeadSettingModel;

    /**
     * @var BlacklistIpService
     */
    private BlacklistIpService $blacklistIpService;

    /**
     * @var GeneralListService
     */
    private GeneralListService $generalListService;

    /**
     * @var FormListService
     */
    private FormListService $formListService;

    /**
     * @var FormSetting
     */
    private FormSetting $formSettingModel;

    /**
     * FormController constructor.
     * @param Form $form,
     * @param FormStep $formStep,
     * @param FormQuestion $formQuestion,
     * @param FormQuestionType $formQuestionType,
     * @param FormVariantType $formVariantType,
     * @param FormVisit $formVisit,
     * @param BlacklistIpService $blacklistIpService,
     * @param GeneralListService $generalListService,
     * @param FormListService $formListService,
     * @param ContactState $contactState,
     * @param FormLead $formLead,
     * @param FormPartialLead $formPartialLead,
     * @param User $user,
     * @param GlobalPartialLeadSetting $globalPartialLeadSetting,
     * @param FormSetting $formSetting
     */
    public function __construct(
        Form $form,
        FormStep $formStep,
        FormQuestion $formQuestion,
        FormQuestionType $formQuestionType,
        FormVariantType $formVariantType,
        FormVisit $formVisit,
        BlacklistIpService $blacklistIpService,
        GeneralListService $generalListService,
        FormListService $formListService,
        ContactState $contactState,
        FormLead $formLead,
        FormPartialLead $formPartialLead,
        User $user,
        GlobalPartialLeadSetting $globalPartialLeadSetting,
        FormSetting $formSetting
    ) {
        $this->middleware('jwt.auth')->except(['showByKey']);
        $this->middleware('leadgen.subscription')->only(['store', 'duplicate']);
        $this->middleware('onetool.subscription')->only(['store', 'duplicate']);
        $this->formModel = $form;
        $this->formStepModel = $formStep;
        $this->formQuestionModel = $formQuestion;
        $this->formQuestionTypeModel = $formQuestionType;
        $this->formVariantTypeModel = $formVariantType;
        $this->formVisitModel = $formVisit;
        $this->blacklistIpService = $blacklistIpService;
        $this->generalListService = $generalListService;
        $this->formListService = $formListService;
        $this->contactStateModel = $contactState;
        $this->formLeadModel = $formLead;
        $this->formPartialLeadModel = $formPartialLead;
        $this->userModel = $user;
        $this->globalPartialLeadSettingModel = $globalPartialLeadSetting;
        $this->formSettingModel = $formSetting;
    }

    public function count()
    {
        $count = $this->formModel
            ->where('created_by', Auth::id())
            ->count();

        return $this->apiResponse(200, ['count' => $count]);
    }

    public function getFormLists(Request $request)
    {
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }

    public function index()
    {
        $user = Auth::user();

        $forms = $this->formModel
            ->withCount(['formLeads', 'viewedLeads', 'formTotalLeads']);

        $createdBy = Auth::id();

        if (!empty($_GET['q'])) {
            $q = json_decode(urldecode($_GET['q']), true);

            if (is_array($q) && count($q) > 1) {
                if ($q[0] !== 'created_by') {
                    $forms = $forms->where([
                        'forms.' . $q[0],
                        'like',
                        '%' . $q[1] . '%',
                    ]);
                } elseif ($q[0] === 'created_by' && $user->isAdmin()) {
                    $createdBy = $q[1];
                }
            }
        }

        $forms = $forms->where('created_by', $createdBy);

        if (empty($_GET['page'])) {
            $forms = $forms
                ->latest()
                ->get();

            foreach ($forms as $form) {
                $form->conversion_rate = $form->conversionRate();
                $form->blocked = $form->isBlocked();
                $form->unread_leads = $form->form_total_leads_count - $form->viewed_leads_count;
            }

            return $this->apiResponse(200, $forms->toArray());
        }

        $formsPagination = $forms
            ->latest()
            ->paginate();

        $forms = $formsPagination->items();

        foreach ($forms as $form) {
            $form->conversion_rate = $form->conversionRate();
            $form->blocked = $form->isBlocked();
            $form->unread_leads = $form->form_total_leads_count - $form->viewed_leads_count;
        }

        $pagination = $formsPagination->toArray();

        unset($pagination['data']);
        return $this->apiResponse(200, $forms, '', '', [], $pagination);
    }

    public function show(Form $form)
    {
        $this->authorize('view', $form);

        $form->conversions = $form->conversionCount();

        $form->visitors_count = $form->visitorCount();

        $form->leads_count = $form->leadsCount();

        return $this->apiResponse(200, $form->toArray());
    }

    public function showByKey(Request $request, $key)
    {
        try {
            DB::beginTransaction();

            if ($request->filled('leadgen_visitor_id')) {
                $visitor = Visitor::where(
                    'ref_id',
                    $request->input('leadgen_visitor_id')
                )->first();
            } else {
                $visitor = null;
            }

            $form = $this->formModel->where('key', $key)->firstOrFail();

            if (
                !$form->createdBy->active ||
                $this->blacklistIpService->isIpBlocked($request->ip())
            ) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::SUSPENDED_ACCOUNT,
                    'form access forbidden due to account suspension'
                );
            }

            if ($form->current_experiment_id) {
                $formVisit = null;

                if ($visitor) {
                    $formVisit = $form->formVisits
                        ->where('visitor_id', $visitor->id)
                        ->where('form_experiment_id', $form->current_experiment_id)
                        ->first();
                }

                if (empty($formVisit)) {
                    $currentVariant = $form->currentExperiment->currentVariant();
                } else {
                    // Get already visited variant by this visitor.
                    $currentVariant = $form->formVariants
                        ->find($formVisit->form_variant_id);
                }
            } else {
                $currentVariant = $form->championVariant();
            }

            if (empty($visitor)) {
                $visitor = Visitor::create([
                    'ref_id' => Util::uuid4()
                ]);
            }

            $geolocation = Util::geolocation($request->ip());
            $ContactStates = $this->contactStateModel->where('form_id', $form->id)->get();
            $formPartialLeads = $this->formPartialLeadModel->where('form_id', $form->id)->get();

            $visit = $this->formVisitModel->createFromParams(
                $request,
                $form,
                $currentVariant,
                $visitor,
                $geolocation
            );

            $data = $currentVariant->getGeneratorState();
            $data['formConnections'] = $ContactStates;
            $data['formPartialLead'] = $formPartialLeads;
            $data['environment'] = config(ConfigKeyEnum::APP_ENV);
            $data['visitorId'] = $visitor->ref_id;
            $data['visitId'] = $visit->id;
            $data['formTrackingEvents'] = $form->formTrackingEvents;
            if ($form->currentExperiment) {
                $data['currentExperiment'] = $form->currentExperiment;
            }

            if (is_array($geolocation)) {
                $data['geolocation'] = $geolocation;
            }

            DB::commit();

            if (!$form->isBlockedByGeolocation($geolocation)) {
                $visit->geolocation_forbidden = true;

                $visit->save();

                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::FORM_GEOLOCATION_FORBIDDEN,
                    'form access forbidden'
                );
            }
            return $this->apiResponse(200, $data);
        } catch (\Exception $e) {
            \Sentry\captureException($e);
            DB::rollBack();

            Util::logException($e);

            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_FETCH_ERROR,
                'Unable to load form please try again'
            );
        }
    }

    public function store(StoreForm $request)
    {
        try {
            DB::beginTransaction();

            $form = $this->formModel->create([
                'title' => $request->input('formTitle'),
                'key' => Util::uuid4(),
                'created_by' => Auth::id()
            ]);

            $championVariantType = $this->formVariantTypeModel->champion();

            $formVariant = $form->variants()->firstOrCreate([
                'form_variant_type_id' => $championVariantType->id,
                'title' => $request->input('formTitle'),
                'form_id' => $form->id,
                'choice_formula' => $request->input('choiceFormula'),
                'calculator_field_name' => $request->input('calculator')['fieldName'],
            ]);

            $globalSettings = $this->globalPartialLeadSettingModel->where('user_id', $form->created_by)->first();

            if ($globalSettings) {
                $form->formPartialLeads->updateOrCreate([
                    'enabled' => $globalSettings->enabled,
                    'form_id' => $form->id,
                    'consent_type' => $globalSettings->consent_type
                ]);
            }

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

                if (!empty($step['questions'])) {
                    // save questions
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

            $form->load('variants');

            event(new FormCreated($form, $formVariant));

            return $this->apiResponse(201, $form->toArray());
        } catch (\Exception $e) {
            DB::rollBack();

            Util::logException($e);

            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_CREATE_ERROR,
                'Unable to save form please try again'
            );
        }
    }

    public function update(Request $request, Form $form)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $form->title = $request->input('title');
        $form->save();

        return $this->apiResponse(200, $form->toArray());
    }

    public function setting($id)
    {
        $form = $this->formModel->findOrFail($id);

        $this->authorize('view', $form);

        return $this->apiResponse(200, $form->formSettings());
    }

    public function integration($id)
    {
        $form = $this->formModel->findOrFail($id);
        return $this->apiResponse(200, $form->integration());
    }

    public function saveSetting(UpdateFormSettingRequest $request, $id)
    {
        $form = $this->formModel->findOrFail($id);

        $this->authorize('update', $form);

        $form->formSetting->update($request->all());

        $form->formEmailNotification->update($request->all());

        $form->formPartialLeads->update([
            'enabled' => $request->partial_leads,
            'form_id' => $request->form_id,
            'consent_type' => $request->consent_type
        ]);

        $user = $this->userModel->where('id', $form->created_by)->first();
        $user->globalPartialLeadSetting = $user->globalPartialLeadSetting;

        return $this->apiResponse(200, $form->formSettings());
    }

    public function duplicate(Form $form)
    {
        $this->authorize('update', $form);

        $duplicateForm = $form->duplicateWithVariantsAndSettings();

        $duplicateForm->form_leads_count = $duplicateForm->formLeads->count();
        $duplicateForm->form_visits_count = $duplicateForm->formVisits->count();
        $duplicateForm->visitor_count = $duplicateForm->visitorCount();
        $duplicateForm->conversion_count = $duplicateForm->conversionCount();

        if ($duplicateForm) {
            return $this->apiResponse(
                200,
                $duplicateForm->toArray()
            );
        }

        return $this->apiResponse(
            400,
            [],
            ErrorTypes::RESOURCE_COPY_ERROR,
            "Unable to duplicate form."
        );
    }

    public function archive(Form $form)
    {
        $this->authorize('delete', $form);

        $form->delete();

        return $this->apiResponse(200);
    }

    public function resetFormStatus(Form $form)
    {
        $this->authorize('resetFormStatus', $form);

        try {
            DB::beginTransaction();

            $this->formLeadModel->where('form_id', $form->id)->delete();
            $this->formVisitModel->where('form_id', $form->id)->delete();

            DB::commit();
            return $this->apiResponse(200);
        } catch (\Exception $e) {
            DB::rollBack();
            Sentry\captureException($e);
            return null;
        }
    }

    public function massDestroy(Form $form, string $formsArr)
    {
        $this->formListService->massDestroy($form, $formsArr);
        return $this->apiResponse(200);
    }

    public function massDuplicate(string $formsArr)
    {
        $this->formListService->massDuplicate($formsArr);
        return $this->apiResponse(200);
    }

    public function share(ShareForm $request, Form $form)
    {
        $this->authorize('share', $form);

        $toUser   = User::findOrFail($request->input('to_user_id'));
        $fromUser = User::findOrFail($request->input('from_user_id'));

        if ($form->created_by !== $fromUser->id) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_COPY_ERROR,
                'form_id doesn\'t belong to from_user_id'
            );
        }

        $newForm = $form->duplicate();

        if (empty($newForm)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_COPY_ERROR,
                "Unable to duplicate form."
            );
        }

        $newForm->title      = $request->input('title');
        $newForm->created_by = $toUser->id;
        $newForm->save();

        return $this->apiResponse(200);
    }

    /**
     * Update footer text of a form
     *
     * @param  integer $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateFooterText(int $id, Request $request)
    {
        try {
            $this->setting($id);
            $formSetting = $this->formSettingModel->updateFooterText(
                $id,
                $request->footerText,
                $request->allStepsFooter
            );
            return $this->apiResponse(200, $formSetting->toArray());
        } catch (\Exception $e) {
            \Sentry\captureException($e);
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'Unable to update footer text. Please try again.'
            );
        }
    }
}
