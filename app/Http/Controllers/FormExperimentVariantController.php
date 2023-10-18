<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Form;
use App\FormExperiment;
use App\FormExperimentVariant;
use Log;

class FormExperimentVariantController extends Controller
{
    protected $formModel;

    protected $formExperimentModel;

    protected $formExperimentVariantModel;

    public function __construct(
        Form $form,
        FormExperiment $formExperiment,
        FormExperimentVariant $formExperimentVariant
    ) {

        $this->middleware('jwt.auth');

        $this->formModel = $form;

        $this->formExperimentModel = $formExperiment;

        $this->formExperimentVariantModel = $formExperimentVariant;
    }

    public function index(Form $form, FormExperiment $experiment)
    {
        $this->authorize('view', $form);

        return $this->apiResponse(200, $experiment->formExperimentVariants->toArray());
    }
}
