<?php

namespace App\Http\Controllers;

use App\Form;
use App\Models\FormTemplate;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use Illuminate\Http\Request;
use App\Modules\Security\Services\AuthService;

class FormTemplateBrowserController extends Controller
{
    protected $formTemplateModel;
    protected $formModel;

    /**
     * AuthService instance.
     */
    protected AuthService $authService;

    /**
     * Constructor.
     *
     * @param FormTemplate $formTemplate
     * @param Form $form
     */
    public function __construct(
        FormTemplate $formTemplate,
        Form $form,
        AuthService $authService
    ) {
        $this->middleware('jwt.auth');
        $this->formTemplateModel = $formTemplate;
        $this->formModel = $form;
        $this->authService = $authService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userFormTemplates = $this->formTemplateModel::with(
            'templateForm',
            'templateIndustries',
            'templateCategories',
            'primaryCategory',
            'templateImage'
        )->withCount('templateSteps')->get();

        return $this->apiResponse(200, $userFormTemplates->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function useTemplate(Request $request, $formId)
    {
        $formValue = $this->formModel->findOrFail($formId);
        $duplicateForm = $formValue->duplicateWithVariantsAndSettings(true, false);
        if (empty($duplicateForm)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_COPY_ERROR,
                "Unable to duplicate form."
            );
        }

        $duplicateForm->title      = $request->input('formTitle');
        $duplicateForm->created_by = $this->authService->getUserId();
        $duplicateForm->template = false;
        $duplicateForm->save();

        return $this->apiResponse(200, $duplicateForm->toArray());
    }
}
