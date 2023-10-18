<?php

namespace App\Http\Controllers;

use App\Models\FormTemplateIndustry;

class FormTemplateIndustryController extends Controller
{
    /**
     * FormTemplateIndustry instance.
     *
     * @var FormTemplateIndustry
     */
    protected $formTemplateIndustry;

    /**
     * Constructor.
     *
     * @param FormTemplateIndustry $formTemplateIndustry
     */
    public function __construct(
        FormTemplateIndustry $formTemplateIndustry
    ) {
        $this->middleware('jwt.auth');
        $this->formTemplateIndustry = $formTemplateIndustry;
    }

    /**
     * Get list of Form Template Industries.
     *
     * @return Response
     */
    public function index()
    {
        $formTemplateIndustries = $this->formTemplateIndustry->all()->toArray();
        return $this->apiResponse(200, $formTemplateIndustries);
    }
}
