<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Form;
use App\FormExperiment;
use App\FormLead;
use App\FormQuestion;
use App\FormVisit;
use App\FormVariant;
use App\FormQuestionResponse;
use App\Visitor;
use App\Models\GlobalPartialLeadSetting;
use App\Models\UserLimitation;
use App\Http\Requests\StoreLeadRequest;
use App\Enums\QuestionTypesEnum;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\TimePeriodsEnum;
use App\Enums\PackageBuilder\FeatureEnum;
use Facades\App\Services\Util;
use App\Events\LeadCreated;
use App\FormHiddenFieldResponse;
use Auth;
use DB;
use Agent;
use Sentry;
use App\User;
use Illuminate\Http\JsonResponse;
use App\Modules\Security\Services\AuthService;
use Illuminate\Support\Facades\Mail;
use App\Mail\PartialLeadsLimitReached;
use App\Mail\LeadLimitReached;

class LeadController extends Controller
{
    /**
     * @var FormLead
     */
    protected $formLeadModel;

    /**
     * @var Form
     */
    protected $formModel;

    /**
     * @var FormQuestion
     */
    protected $formQuestionModel;

    /**
     * @var FormQuestionResponse
     */
    protected $formQuestionResponseModel;

    /**
     * @var FormVisit
     */
    protected $formVisitModel;

    /**
     * @var Visitor
     */
    protected $visitorModel;

    /**
     * @var FormVariant
     */
    protected $formVariantModel;

    /**
     * @var User
     */
    protected $userModel;

    /**
     * Current number of leads exported.
     *
     * @var integer
     */
    protected $exported;

    /**
     * Number of leads to be exported.
     *
     * @var integer
     */
    protected $exportCount;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * @var GlobalPartialLeadSetting
     */
    protected $globalPartialLeadSettingModel;

    /**
     * @var UserLimitation
     */
    protected $userLimitationModel;

    /**
     * LeadController constructor.
     * @param Form $form,
     * @param FormLead $formLead,
     * @param FormQuestion $formQuestion,
     * @param FormQuestionResponse $formQuestionResponse,
     * @param FormVisit $formVisit,
     * @param Visitor $visitor,
     * @param FormVariant $formVariant,
     * @param User $user,
     * @param AuthService $authService,
     * @param GlobalPartialLeadSetting $globalPartialLeadSetting
     * @param UserLimitation $userLimitation
     */
    public function __construct(
        Form $form,
        FormLead $formLead,
        FormQuestion $formQuestion,
        FormQuestionResponse $formQuestionResponse,
        FormVisit $formVisit,
        Visitor $visitor,
        FormVariant $formVariant,
        User $user,
        AuthService $authService,
        GlobalPartialLeadSetting $globalPartialLeadSetting,
        UserLimitation $userLimitation
    ) {
        $this->formModel = $form;
        $this->formLeadModel = $formLead;
        $this->formQuestionModel = $formQuestion;
        $this->formQuestionResponseModel = $formQuestionResponse;
        $this->formVisitModel = $formVisit;
        $this->visitorModel = $visitor;
        $this->formVariantModel = $formVariant;
        $this->userModel = $user;
        $this->authService = $authService;
        $this->globalPartialLeadSettingModel = $globalPartialLeadSetting;
        $this->userLimitationModel = $userLimitation;
        $this->middleware('jwt.auth')->except(['store', 'storePartialLead']);
    }

    public function count()
    {
        $createdFormIds = $this->formModel
            ->where('created_by', Auth::id())
            ->pluck('id')
            ->toArray();

        $count = $this->formLeadModel
            ->whereIn('form_id', $createdFormIds)
            ->count();

        return $this->apiResponse(200, ['count' => $count]);
    }

    public function averageConversionRate()
    {
        $forms = $this->formModel
            ->where('created_by', Auth::id())
            ->get();
        $rate = 0;
        foreach ($forms as $form) {
            $rate += $form->conversionRate();
        }

        $formCount = $forms->count();
        if ($formCount > 0) {
            $rate = $rate / $formCount;
            $rate = round($rate, 2);
        }

        return $this->apiResponse(200, ['rate' => $rate]);
    }

    /**
     * Store Lead
     * @param StoreLeadRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreLeadRequest $request)
    {
        try {
            $form = $this->formModel->where('key', $request->input('key'))->firstOrFail();
            $formVariant = $form->variants()->where('id', $request->input('id'))->firstOrFail();

            if (!$this->verifyResponseEnabled($form)) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::ACCEPT_RESPONSES_DISABLED,
                    'Lead submission is not allowed on this form'
                );
            }

            if (!$this->verifyResponseLimit($form, $request->ip())) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::RESPONSE_LIMIT_REACHED,
                    'You have crossed the response submission limit'
                );
            }

            if ($form->formSetting->enable_google_recaptcha) {
                if (!$this->verifyRecaptcha($request, $form)) {
                    return $this->apiResponse(
                        400,
                        [],
                        ErrorTypes::RECAPTCHA_INVALID_RESPONSE,
                        'Please submit correct grecaptcha response'
                    );
                }
            }

            $user = $this->userModel->findOrFail($form->created_by);
            $planFeatures = $user->plan()->planFeatures()->where('slug', FeatureEnum::LEADS)->first();
            $planFeatureProperties = $planFeatures->featureProperties->where('feature_id', $planFeatures->id);

            $details = [
                'name' => $user->name,
                'resetPeriod' => $planFeatureProperties[0]->reset_period
            ];

            $limitationData = $user->userLimitation->where('user_id', $user->id)->first();
            $leadLimit = $planFeatureProperties[0]->value;
            $leadCount = $this->userModel->leadsCount($user, $planFeatureProperties[0]->reset_period);
            $currentLeadCount = $leadCount + 1;
            $quotaPercentage = ($currentLeadCount / $leadLimit) * 100;
            $threshHold = 70;

            if ($user->isCustomer() && $this->userModel->canCreateLeads($user)) {
                // user can create leads, reset fields
                $limitationData->lead_limit_reached = false;
                $limitationData->lead_limit_email_sent = false;
                $limitationData->save();

                if ($quotaPercentage >= $threshHold && !$limitationData->lead_limit_email_sent) {
                    // user can create leads, 70% exhausted
                    $limitationData->lead_limit_email_sent = true;
                    $limitationData->save();
                    $details['threshHold'] = $threshHold;
                    Mail::to($user->email)->send(new LeadLimitReached($details));
                }
            } elseif ($user->isCustomer() && !$this->userModel->canCreateLeads($user)) {
                // user can't create leads
                if ($limitationData && !$limitationData->lead_limit_reached) {
                    $limitationData->lead_limit_reached = true;
                    $limitationData->lead_limit_email_sent = true;
                    $limitationData->save();
                    $details['threshHold'] = 100;
                    Mail::to($user->email)->send(new LeadLimitReached($details));
                }
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::LEADS_LIMIT_REACHED,
                    'You have crossed the lead submission limit'
                );
            }

            DB::beginTransaction();

            $currentExperimentId = ($request->input('previewMode') === 'true') ? null : $form->current_experiment_id;
            $previewMode = ($request->input('previewMode') === 'true');

            $formVisitId = $this->getFormVisitId($request, $form, $formVariant, $previewMode);

            $formLead = $this->formLeadModel->updateOrCreate(
                ['id' => $request->input('lead_id')],
                [
                    'reference_no' => Util::uuid4(),
                    'form_id' => $form->id,
                    'form_variant_id' => $formVariant->id,
                    'form_visit_id' => $formVisitId,
                    'form_experiment_id' => $currentExperimentId,
                    'calculator_total' => $request->input('calculatorTotal'),
                    'is_partial' => false
                ]
            );

            $formLead->setAttribute('claim_url', $request->claim_url);
            foreach ($request->input('steps') as $inputStep) {
                if (!empty($inputStep['skipped']) && $inputStep['skipped'] === 'true') {
                    continue;
                }

                if (!empty($inputStep['questions'])) {
                    foreach ($inputStep['questions'] as $inputQuestion) {
                        if ($inputQuestion['type'] === QuestionTypesEnum::ADDRESS) {
                            $fieldValues = [];
                            foreach ($inputQuestion['fields'] as $field) {
                                $fieldValues[$field['id']] = htmlspecialchars($field['value']);
                            }

                            $qValue = json_encode($fieldValues);
                        } else {
                            $qValue = !isset($inputQuestion['value'])
                            ? '' : $inputQuestion['value'];
                            if (is_array($qValue)) {
                                $qValue = json_encode($qValue);
                            } else {
                                $qValue = htmlspecialchars($qValue, ENT_NOQUOTES);
                                if (strpos($qValue, '&amp;') !== false) {
                                    $qValue = str_replace('&amp;', '&', $qValue);
                                }
                            }
                        }

                        $this->formQuestionResponseModel->updateOrCreate([
                            'response' => $qValue,
                            'form_lead_id' => $formLead->id,
                            'form_question_id' => $inputQuestion['dbId']
                        ]);
                    }
                }
            }

            $this->createHiddenFieldResponse($request, $formLead);
            event(new LeadCreated($formLead));
            DB::commit();
            $data = $formLead->load(['formVisit.visitor'])->toArray();
            return $this->apiResponse(201, $data);
        } catch (\Exception $e) {
            DB::rollback();
            Util::logException($e);
            Sentry\captureException($e);
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::LEAD_SAVE_ERROR,
                'Unable to save lead. Please try again.'
            );
        }
    }

    /**
     * Store Partial Lead
     * @param StoreLeadRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePartialLead(StoreLeadRequest $request)
    {
        $form = $this->formModel
            ->where('key', $request->input('key'))
            ->firstOrFail();
        $user = $this->userModel
            ->where('id', $form->created_by)
            ->firstOrFail();
        $formVariant = $form->variants()
            ->where('id', $request->input('id'))
            ->firstOrFail();
        if (!$this->verifyResponseEnabled($form)) {
            return $this->apiResponse(
                400,
                [],
                'accept_responses_disabled',
                'Lead submission is not allowed on this form'
            );
        }

        if (!$this->verifyResponseLimit($form, $request->ip())) {
            return $this->apiResponse(
                400,
                [],
                'response_limit_reached',
                'You have crossed the response submission limit'
            );
        }
        $plan = $user->plan();
        $planFeatures = $plan->planFeatures->where('slug', FeatureEnum::PARTIAL_LEADS)->first();
        $planFeatureProperties = $planFeatures->featureProperties->where('feature_id', $planFeatures->id);
        $details = [
            'name' => $user->name,
            'resetPeriod' => $planFeatureProperties[0]->reset_period
        ];
        $partialData = $this->globalPartialLeadSettingModel->where('user_id', $user->id)->first();
        if (!$this->userModel->canCreatePartialLead($user)) {
            if ($partialData && !$partialData->limit_reached) {
                Mail::to($user->email)->send(new PartialLeadsLimitReached($details));
                $partialData->limit_reached = true;
                $partialData->save();
            }
            return $this->apiResponse(
                400,
                [],
                'partial_leads_limit_reached',
                'You have crossed the partial leads submission limit'
            );
        } else {
            if ($partialData && $partialData->limit_reached) {
                $partialData->limit_reached = false;
                $partialData->save();
            }
        }
        try {
            DB::beginTransaction();
            if ($request->input('previewMode') === 'true') {
                $currentExperimentId = null;
                $previewMode = true;
            } else {
                $currentExperimentId = $form->current_experiment_id;
                $previewMode = false;
            }

            $formVisitId = $this->getFormVisitId($request, $form, $formVariant, $previewMode);
            if (!empty($request->input('lead_id'))) {
                $formLead = $this->formLeadModel->find($request->input('lead_id'));
                $formLead->update([
                    'reference_no' => $formLead->reference_no,
                    'form_id' => $formLead->form_id,
                    'form_variant_id' => $formLead->form_variant_id,
                    'form_visit_id' => $formLead->form_visit_id,
                    'form_experiment_id' => $formLead->form_experiment_id,
                    'calculator_total' => $formLead->calculator_total,
                    'is_partial' => $formLead->is_partial
                ]);
            } else {
                $formLead = $this->formLeadModel->create([
                    'reference_no' => Util::uuid4(),
                    'form_id' => $form->id,
                    'form_variant_id' => $formVariant->id,
                    'form_visit_id' => $formVisitId,
                    'form_experiment_id' => $currentExperimentId,
                    'calculator_total' => $request->input('calculatorTotal'),
                    'is_partial' => true
                ]);
            }
            $formLead->setAttribute('claim_url', $request->claim_url);
            $formLead->setAttribute('lead_id', $formLead->id);
            foreach ($request->input('steps') as $inputStep) {
                if (!empty($inputStep['skipped']) && $inputStep['skipped'] === 'true') {
                    continue;
                }

                if (!empty($inputStep['questions'])) {
                    foreach ($inputStep['questions'] as $inputQuestion) {
                        if ($inputQuestion['type'] === QuestionTypesEnum::ADDRESS) {
                            $fieldValues = [];
                            foreach ($inputQuestion['fields'] as $field) {
                                $fieldValues[$field['id']] = htmlspecialchars($field['value']);
                            }

                            $qValue = json_encode($fieldValues);
                        } else {
                            $qValue = !isset($inputQuestion['value'])
                            ? '' : $inputQuestion['value'];
                            if (is_array($qValue)) {
                                $qValue = json_encode($qValue);
                            } else {
                                $qValue = htmlspecialchars($qValue, ENT_NOQUOTES);
                                if (strpos($qValue, '&amp;') !== false) {
                                    $qValue = str_replace('&amp;', '&', $qValue);
                                }
                            }
                        }

                        $formQuestion = $this->formQuestionResponseModel->updateOrCreate(
                            [
                                'form_question_id' => $inputQuestion['dbId'],
                                'form_lead_id' => $formLead->id
                            ],
                            [
                                'response' => $qValue,
                                'form_lead_id' => $formLead->id,
                                'form_question_id' => $inputQuestion['dbId']
                            ]
                        );
                        $formLead->setAttribute('form_question_response', $formQuestion);
                    }
                }
            }

            $this->createHiddenFieldResponse($request, $formLead);
            DB::commit();
            $data = $formLead->load(['formVisit.visitor'])->toArray();
            return $this->apiResponse(201, $data);
        } catch (\Exception $e) {
            DB::rollback();
            Sentry\captureException($e);
        }
    }

    /**
     * Get partial leads count for different time periods.
     * @param int $id
     * @return null|JsonResponse
     */
    public function getPartialLeadCounts(int $id): JsonResponse
    {
        $authUser = $this->authService->getUser();

        if ($authUser->id !== $id) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }
        $counts = [];
        $timePeriods = [
            TimePeriodsEnum::MONTHLY,
            TimePeriodsEnum::YEARLY,
            TimePeriodsEnum::AS_PER_PLAN,
            TimePeriodsEnum::NONE
        ];

        foreach ($timePeriods as $timePeriod) {
            $counts[$timePeriod] = $this->userModel->partialLeadsCount($authUser, $timePeriod);
        }

        return $this->apiResponse(200, $counts);
    }

    /**
     * Get leads count for different time periods.
     * @param int $id
     * @return null|JsonResponse
     */
    public function getleadsCountPerTimePeriod(int $id): JsonResponse
    {
        $authUser = $this->authService->getUser();
        if ($authUser->id !== $id) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $counts = [];
        $timePeriods = [
            TimePeriodsEnum::MONTHLY,
            TimePeriodsEnum::YEARLY,
            TimePeriodsEnum::AS_PER_PLAN,
            TimePeriodsEnum::NONE
        ];

        foreach ($timePeriods as $timePeriod) {
            $counts[$timePeriod] = $this->userModel->leadsCount($authUser, $timePeriod);
        }

        return $this->apiResponse(200, $counts);
    }

    public function getFormLeads(Form $form, $variant = null, FormExperiment $experiment = null)
    {

        $this->authorize('view', $form);
        $leads = $this->formLeadModel
            ->where('form_id', $form->id);
        if (!empty($variant)) {
            $leads = $leads->where('form_variant_id', $variant);
        } else {
            $leads = $leads->where('form_variant_id', $form->championVariant()->id);
        }

        if (!empty($experiment)) {
            $leads = $leads->where('form_experiment_id', $experiment->id);
        }

        $leadsPagination = $leads
            ->with('formVisit')
            ->with('questionResponses.formQuestion.formQuestionType')
            ->with('hiddenFieldResponses.formHiddenField')
            ->latest()
            ->paginate();
        $leads = $leadsPagination->items();
        $pagination = $leadsPagination->toArray();
        unset($pagination['data']);
        return $this->apiResponse(200, $leads, '', '', [], $pagination);
    }

    public function destroy(FormLead $lead)
    {
        $this->authorize('view', $lead->form);

        try {
            DB::beginTransaction();

            $lead->forceDelete();
            $lead->formVisit->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Util::logException($e);

            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_DELETE_ERROR,
                'Unable to save lead please try again.'
            );
        }

        return $this->apiResponse(200);
    }

    public function bulkDelete(Form $form, $variant)
    {
        $this->authorize('delete', $form);
        $this->formLeadModel
            ->where('form_variant_id', $variant)
            ->forceDelete();
        $this->formVariantModel
            ->where('id', $variant)
            ->forceDelete();

        return $this->apiResponse(200, $this->formLeadModel->toArray());
    }

    /**
     * Export both partial and leads
     * @param integer $form
     * @param integer $variant
     * @param string $format
     */
    public function export(int $form, int $variant, string $format)
    {
        if (!in_array($format, ['csv', 'json'])) {
            dd('only csv and json format are supported');
        }

        $form = Form::findOrFail($form);
        $this->authorize('view', $form);
        $variant = FormVariant::findOrFail($variant);
        $query = $this->formLeadModel
            ->with('questionResponses.formQuestion.formQuestionType')
            ->where('form_id', $form->id)
            ->where('form_variant_id', $variant->id);

        $this->exportCount = $query->count();
        if ($this->exportCount === 0) {
            die('<script>window.close();</script>');
        }

        $this->streamExport(
            [
                collect(),
                $this->exportLeadQuestions($variant), $this->exportLeadHiddenFields($variant)
            ],
            $format,
            true
        );

        $characters = array('\'', '"', ',', '[', ']', '{', '}', '(', ')', '/', '\\');
        $exportFileName = str_replace($characters, ' ', $variant->title);

        return response()->streamDownload(function () use ($form, $variant, $query, $format) {
            $query->chunk(500, function ($leads) use ($form, $variant, $query, $format) {
                $this->streamExport(
                    [
                        $this->exportPrepareLeadsData($leads, $format),
                        $this->exportLeadQuestions($variant),
                        $this->exportLeadHiddenFields($variant)
                    ],
                    $format
                );
            });

            $this->streamExport(
                [
                    collect(),
                    $this->exportLeadQuestions($variant),
                    $this->exportLeadHiddenFields($variant)
                ],
                $format,
                false,
                true
            );
        }, $exportFileName . '.' . $format);
    }

    /**
     * Export Partial leads only
     * @param integer $form
     * @param integer $variant
     * @param string $format
     */
    public function exportPartial(int $form, int $variant, string $format)
    {
        if (!in_array($format, ['csv', 'json'])) {
            dd('only csv and json format are supported');
        }

        $form = Form::findOrFail($form);
        $this->authorize('view', $form);
        $variant = FormVariant::findOrFail($variant);
        $query = $this->formLeadModel
            ->with('questionResponses.formQuestion.formQuestionType')
            ->where('form_id', $form->id)
            ->where('form_variant_id', $variant->id)
            ->where('is_partial', 1);

        $this->exportCount = $query->count();
        if ($this->exportCount === 0) {
            die('<script>window.close();</script>');
        }

        $this->streamExport(
            [
                collect(),
                $this->exportLeadQuestions($variant), $this->exportLeadHiddenFields($variant)
            ],
            $format,
            true
        );

        $characters = array('\'', '"', ',', '[', ']', '{', '}', '(', ')', '/', '\\');
        $exportFileName = str_replace($characters, ' ', $variant->title);

        return response()->streamDownload(function () use ($form, $variant, $query, $format) {
            $query->chunk(500, function ($leads) use ($form, $variant, $query, $format) {
                $this->streamExport(
                    [
                        $this->exportPrepareLeadsData($leads, $format),
                        $this->exportLeadQuestions($variant),
                        $this->exportLeadHiddenFields($variant)
                    ],
                    $format
                );
            });

            $this->streamExport(
                [
                    collect(),
                    $this->exportLeadQuestions($variant),
                    $this->exportLeadHiddenFields($variant)
                ],
                $format,
                false,
                true
            );
        }, $exportFileName . '.' . $format);
    }

    /**
     * Export leads only
     * @param integer $form
     * @param integer $variant
     * @param string $format
     */
    public function exportLeads(int $form, int $variant, string $format)
    {
        if (!in_array($format, ['csv', 'json'])) {
            dd('only csv and json format are supported');
        }

        $form = Form::findOrFail($form);
        $this->authorize('view', $form);
        $variant = FormVariant::findOrFail($variant);
        $query = $this->formLeadModel
            ->with('questionResponses.formQuestion.formQuestionType')
            ->where('form_id', $form->id)
            ->where('form_variant_id', $variant->id)
            ->where('is_partial', 0);

        $this->exportCount = $query->count();
        if ($this->exportCount === 0) {
            die('<script>window.close();</script>');
        }

        $this->streamExport(
            [
                collect(),
                $this->exportLeadQuestions($variant), $this->exportLeadHiddenFields($variant)
            ],
            $format,
            true
        );

        $characters = array('\'', '"', ',', '[', ']', '{', '}', '(', ')', '/', '\\');
        $exportFileName = str_replace($characters, ' ', $variant->title);

        return response()->streamDownload(function () use ($form, $variant, $query, $format) {
            $query->chunk(500, function ($leads) use ($form, $variant, $query, $format) {
                $this->streamExport(
                    [
                        $this->exportPrepareLeadsData($leads, $format),
                        $this->exportLeadQuestions($variant),
                        $this->exportLeadHiddenFields($variant)
                    ],
                    $format
                );
            });

            $this->streamExport(
                [
                    collect(),
                    $this->exportLeadQuestions($variant),
                    $this->exportLeadHiddenFields($variant)
                ],
                $format,
                false,
                true
            );
        }, $exportFileName . '.' . $format);
    }

    protected function streamExport($data, $format, $begin = false, $end = false)
    {
        if ($begin) {
            $this->exported = 0;
        }

        if ($format === 'json') {
            $this->exportJSON($data, $begin, $end);
        } elseif ($format === 'csv') {
            $this->exportCSV($data, $begin);
        }
    }

    protected function exportLeadQuestions($variant)
    {
        $leadQuestions = [];
        foreach ($variant->formSteps as $formStep) {
            foreach ($formStep->formQuestions as $formQuestion) {
                $leadQuestions[$formQuestion->id] = sprintf('"%s"', $formQuestion->config['title']);
            }
        }

        return $leadQuestions;
    }

    protected function exportLeadHiddenFields($variant)
    {
        $leadHiddenFields = [];

        foreach ($variant->formHiddenFields as $hiddenField) {
            $leadHiddenFields[$hiddenField->id] = sprintf('"%s"', $hiddenField->name);
        }

        return $leadHiddenFields;
    }

    protected function exportPrepareLeadsData($leads, $format)
    {
        $leadsData = collect();
        foreach ($leads as $lead) {
            $leadItem = collect();
            foreach ($lead->questionResponses as $questionResponse) {
                $response = '';
                $qType = $questionResponse->formQuestion->formQuestionType->type;
                $qConfig = $questionResponse->formQuestion->config;
                if (!empty($questionResponse->response)) {
                    if ($qType === QuestionTypesEnum::ADDRESS) {
                        foreach ($questionResponse->response as $ak => $av) {
                            if ($qConfig['fields'][$ak]['enabled'] === 'false') {
                                continue;
                            }
                            $response .= $ak . ':' . $av . ' | ';
                        }
                        $response = rtrim($response, "| ");
                    } elseif ($qType === QuestionTypesEnum::SINGLE_CHOICE) {
                        if (is_array($questionResponse->response)) {
                            $response = $questionResponse->response['label'];
                        } else {
                            $response = $questionResponse->response;
                        }
                    } elseif ($qType === QuestionTypesEnum::MULTIPLE_CHOICE) {
                        if (is_array($questionResponse->response)) {
                            $response = array_map(function ($choice) {
                                return $choice['label'];
                            }, $questionResponse->response);
                            $response = implode(', ', $response);
                        } else {
                            $response = $questionResponse->response;
                        }
                    } elseif ($qType === QuestionTypesEnum::GDPR) {
                        if (is_array($questionResponse->response)) {
                            if (count($questionResponse->response) > 0 && is_array($questionResponse->response[0])) {
                                $response = implode(', ', array_map(function ($choice, $index) {
                                    return 'option ' . ($choice['id']);
                                }, $questionResponse->response, array_keys($questionResponse->response)));
                            } else {
                                $response = implode(',', $questionResponse->response);
                            }
                        } else {
                            $response = $questionResponse->response;
                        }
                    } else {
                        $response = $questionResponse->response;
                    }
                }

                if ($format === 'csv') {
                    $response = '"' . $response . '"';
                }

                if ($qType === QuestionTypesEnum::GDPR) {
                    if ($qConfig['enabled'] === 'false') {
                        continue;
                    }
                }

                $leadItem->put($questionResponse->formQuestion->id, $response);
            }

            foreach ($lead->hiddenFieldResponses as $hiddenFieldResponse) {
                $response = $hiddenFieldResponse->response;
                if ($format === 'csv') {
                    $response = '"' . $response . '"';
                }

                $leadItem->put('hidden_field_' . $hiddenFieldResponse->formHiddenField->id, $response);
            }

            $leadItem->put('lead_calculator_total', $lead->calculator_total);
            $leadItem->put('lead_type', $lead->is_partial);
            $leadItem->put('lead_created_at', $lead->created_at);
            $leadItem->put('lead_source_url', $lead->formVisit->source_url);
            $leadItem->put('lead_device_type', $lead->formVisit->device_type);
            $leadItem->put('lead_browser', $lead->formVisit->browser);
            $leadItem->put('lead_ip', $lead->formVisit->ip);
            if ($leadItem->count() > 0) {
                $leadsData->push($leadItem);
            }
        }

        return $leadsData;
    }

    protected function exportCSV($data, $begin = false)
    {
        list($leadsData, $leadQuestions, $leadHiddenFields) = $data;

        if ($begin) {
            $csvHead = implode(',', array_values($leadQuestions));
            if (!empty($leadHiddenFields)) {
                $csvHead .= ',' . implode(',', array_values($leadHiddenFields));
            }

            $csvHead .= ', ' . implode(', ', [
                'Lead Calculator Total',
                'Lead Type',
                'Lead Created At',
                'Lead Source URL',
                'Lead Device Type',
                'Lead Browser',
                'Lead IP',
            ]);
            $csvHead .= "\n";

            echo $csvHead;

            return;
        }

        foreach ($leadsData as $leadData) {
            $leadResponse = $leadData->toArray();
            $csvBodyLine = [];
            foreach ($leadQuestions as $leadQuestionId => $leadQuestionTitle) {
                if (empty($leadResponse[$leadQuestionId])) {
                    $csvBodyLine[] = '';
                } else {
                    $csvBodyLine[] = $leadResponse[$leadQuestionId];
                }
            }

            foreach ($leadHiddenFields as $leadHiddenFieldId => $leadHiddenFieldName) {
                if (empty($leadResponse['hidden_field_' . $leadHiddenFieldId])) {
                    $csvBodyLine[] = '';
                } else {
                    $csvBodyLine[] = $leadResponse['hidden_field_' . $leadHiddenFieldId];
                }
            }

            $csvBodyLine[] = $leadData->get('lead_calculator_total');
            $csvBodyLine[] = $leadData->get('lead_type') === 1 ? 'Partial' : 'Lead';
            $csvBodyLine[] = $leadData->get('lead_created_at');
            $csvBodyLine[] = $leadData->get('lead_source_url');
            $csvBodyLine[] = $leadData->get('lead_device_type');
            $csvBodyLine[] = $leadData->get('lead_browser');
            $csvBodyLine[] = $leadData->get('lead_ip');
            $csvBodyLine = implode(',', $csvBodyLine) . "\n";
            echo $csvBodyLine;
            $this->exported++;
        }
    }

    protected function exportJSON($data, $begin = false, $end = false)
    {
        list($leadsData, $leadQuestions, $leadHiddenFields) = $data;
        if ($begin) {
            echo '[';
            return;
        }

        foreach ($leadsData as $leadData) {
            $leadJson = [];
            foreach ($leadData as $leadDataId => $leadDataVal) {
                if (!empty($leadQuestions[$leadDataId])) {
                    $leadJson[$leadQuestions[$leadDataId]] = $leadDataVal;
                } elseif ($leadDataId === 'lead_calculator_total') {
                    $leadJson['Lead Calculator Total'] = $leadDataVal;
                } elseif ($leadDataId === 'lead_type') {
                    $leadJson['Lead Type'] = $leadDataVal === 1 ? 'Partial' : 'Lead';
                } elseif ($leadDataId === 'lead_created_at') {
                    $leadJson['Lead Created At'] = $leadDataVal->toDateTimeString();
                } elseif ($leadDataId === 'lead_source_url') {
                    $leadJson['Lead Source URL'] = $leadDataVal;
                } elseif ($leadDataId === 'lead_device_type') {
                    $leadJson['Lead Device Type'] = $leadDataVal;
                } elseif ($leadDataId === 'lead_browser') {
                    $leadJson['Lead Browser'] = $leadDataVal;
                } elseif ($leadDataId === 'lead_ip') {
                    $leadJson['Lead IP'] = $leadDataVal;
                } else {
                    $leadJson[$leadHiddenFields[str_replace('hidden_field_', '', $leadDataId)]] = $leadDataVal;
                }
            }

            echo json_encode($leadJson) . ($this->exported ===  $this->exportCount - 1 ? '' : ',');
            $this->exported++;
        }

        if ($end) {
            echo ']';
            return;
        }
    }

    protected function createHiddenFieldResponse($request, FormLead $formLead)
    {
        if ($request->filled('hiddenFields')) {
            $hiddenFields = $request->input('hiddenFields');
            foreach ($hiddenFields as $hiddenField) {
                FormHiddenFieldResponse::create([
                    'response' => $hiddenField['default_value'],
                    'form_hidden_field_id' => $hiddenField['id'],
                    'form_lead_id' => $formLead->id
                ]);
            }
        }
    }

    protected function verifyResponseEnabled(Form $form)
    {
        return $form->formSetting->accept_responses;
    }

    protected function verifyResponseLimit(Form $form, $ip)
    {
        $responseLimit = $form->formSetting->response_limit;
        if ($responseLimit === -1) {
            return true;
        }
        $responsesReceived = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->where('form_visits.ip', '=', $ip)
            ->where('form_visits.form_id', '=', $form['id'])
            ->count();
        if ($responseLimit <= $responsesReceived) {
            return false;
        }

        return true;
    }

    protected function verifyRecaptcha($request, $form)
    {
        $clientHost = parse_url($request->header('origin'), PHP_URL_HOST);
        if (Str::contains(Util::config('leadgen.client_app_url'), $clientHost)) {
            return Util::verifyRecaptcha($request->input('recaptchaResponse'));
        } elseif (Str::contains(Util::config('leadgen.pages_domain'), $clientHost)) {
            return Util::verifyRecaptcha(
                $request->input('recaptchaResponse'),
                Util::config('leadgen.google_irecaptcha_secret_key_pages_domain')
            );
        } elseif (Str::contains(Util::config('leadgen.forms_domain'), $clientHost)) {
            return Util::verifyRecaptcha(
                $request->input('recaptchaResponse'),
                Util::config('leadgen.google_irecaptcha_secret_key_forms_domain')
            );
        } else {
            $form->formSetting = $form->formSetting;
            $form->formSetting->load('googleRecaptchaKey');
            if (empty($form->formSetting->googleRecaptchaKey)) {
                return false;
            } else {
                $secretKey = $form->formSetting->googleRecaptchaKey->secret_key;
                return Util::verifyRecaptcha($request->input('recaptchaResponse'), $secretKey);
            }
        }
    }

    private function getFormVisitId(Request $request, Form $form, FormVariant $formVariant, $previewMode)
    {
        if ($request->filled('formVisitId')) {
            $visit = $this->formVisitModel
                ->find($request->input('formVisitId'));
        } else {
            $visit = null;
        }

        if (empty($visit)) {
            $visitor = $this->createVisitor();
            $visit = $this->createVisit($request, $form, $formVariant, $visitor);
        }

        return $visit->id;
    }

    private function createVisitor()
    {
        return $this->visitorModel->create([
            'ref_id' => Util::uuid4()
        ]);
    }

    private function createVisit(Request $request, Form $form, FormVariant $formVariant, Visitor $visitor)
    {
        $sourceUrl =
        $request->filled('source_url') ? $request->input('source_url') : $request->headers->get('referer');

        return $this->formVisitModel->create([
            'form_id' => $form->id,
            'visitor_id' => $visitor->id,
            'form_experiment_id' => $form->current_experiment_id,
            'os' => Agent::platform(),
            'device_type' => Util::deviceType(),
            'device_name' => Agent::device(),
            'robot' => Agent::robot(),
            'is_robot' => Agent::isRobot(),
            'browser' => Agent::browser(),
            'source_url' => $sourceUrl,
            'ip' => $request->ip(),
            'user_agent' => $request->headers->get('User-Agent'),
            'form_variant_id' => $formVariant->id
        ]);
    }
}
