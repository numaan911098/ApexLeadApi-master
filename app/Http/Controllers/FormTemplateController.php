<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Form;
use App\Models\FormTemplate;
use App\Models\FormTemplateCategory;
use App\Services\FormTemplateBuilderService;
use App\Services\Lists\GeneralListService;
use App\Enums\ErrorTypesEnum as ErrorTypes;

class FormTemplateController extends Controller
{
    /**
     * @var FormTemplateBuilderService
     */
    protected FormTemplateBuilderService $formTemplateBuilderService;

    /**
     * @var GeneralListService
     */
    private GeneralListService $generalListService;

    /**
     * @var Form
     */
    protected Form $formModel;

    /**
     * @var FormTemplate
     */
    protected FormTemplate $formTemplateModel;

    /**
     * @var FormTemplateCategory
     */
    protected FormTemplateCategory $formTemplateCategoryModel;

    /**
     * FormTemplateController constructor.
     * @param FormTemplateBuilderService $formTemplateBuilderService
     * @param GeneralListService $generalListService
     * @param Form $form
     * @param FormTemplate $formTemplate
     * @param FormTemplateCategory $formTemplateCategory
     */
    public function __construct(
        FormTemplateBuilderService $formTemplateBuilderService,
        GeneralListService $generalListService,
        Form $form,
        FormTemplate $formTemplate,
        FormTemplateCategory $formTemplateCategory
    ) {
        $this->middleware('jwt.auth');
        $this->formTemplateBuilderService = $formTemplateBuilderService;
        $this->generalListService = $generalListService;
        $this->formModel = $form;
        $this->formTemplateModel = $formTemplate;
        $this->formTemplateCategoryModel = $formTemplateCategory;
    }

    /**
     * Get list of Templates.
     *
     * @return Response
     */
    public function getFormTemplateLists(Request $request)
    {
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $templateId = $this->formTemplateModel->where('template_id', $request->input('template_id'))->first();
        if (!empty($templateId)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::TEMPLATE_ID_ALREADY_EXIST,
                'This Template ID is already being used.'
            );
        }

        $result =  $this->formTemplateBuilderService->createFormTemplate($request->all());
        return $this->apiResponse(200, $result);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @param FormTemplate
     * @return \Illuminate\Http\Response
     */
    public function show(FormTemplate $formTemplate)
    {
        $this->authorize('view', $formTemplate);
        $result =  $this->formTemplateBuilderService->getFormTemplate($formTemplate->id);
        return $this->apiResponse(200, $result->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FormTemplate $formTemplate)
    {
        $this->authorize('update', $formTemplate);
        $result =  $this->formTemplateBuilderService->updateFormTemplate($request->all(), $formTemplate->id);
        return $this->apiResponse(200, $result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(FormTemplate $formTemplate)
    {
        $this->authorize('delete', $formTemplate);
        $this->formTemplateBuilderService->delteFormTemplate($formTemplate->id);
        return $this->apiResponse(200);
    }
}
