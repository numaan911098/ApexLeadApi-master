<?php

namespace App\Http\Controllers;

use App\Models\FormTemplateCategory;

class FormTemplateCategoryController extends Controller
{
    /**
     * FormTemplateCategory instance.
     *
     * @var FormTemplateCategory
     */
    protected $formTemplateCategory;

    /**
     * Constructor.
     *
     * @param FormTemplateCategory $formTemplateCategory
     */
    public function __construct(
        FormTemplateCategory $formTemplateCategory
    ) {
        $this->middleware('jwt.auth');
        $this->formTemplateCategory = $formTemplateCategory;
    }

    /**
     * Get list of Form Template Categories.
     *
     * @return Response
     */
    public function index()
    {
        $formTemplateCategories = $this->formTemplateCategory->all()->toArray();
        return $this->apiResponse(200, $formTemplateCategories);
    }
}
