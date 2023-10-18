<?php

namespace App\Http\Controllers;

use App\Services\FormConnectionService;
use Illuminate\Http\Request;
use Facades\App\Services\Util;
use App\Http\Requests\StoreContactState;
use App\Models\ContactState;
use App\Form;
use App\FormVariant;
use DB;
use Auth;
use Log;

class FormConnectionController extends Controller
{
    protected FormConnectionService $formConnectionService;
    protected $form;
    protected ContactState $contactState;

    public function __construct(
        Form $form,
        FormConnectionService $formConnectionService,
        ContactState $contactState
    ) {
        $this->formConnectionService = $formConnectionService;
        $this->formModel = $form;
        $this->contactState = $contactState;
    }

    public function index()
    {
        $result =  $this->formConnectionService->getGlobalConnections();
        return $this->apiResponse(200, $result, '', '', []);
    }

    public function show($formId)
    {
        $result =  $this->formConnectionService->getConnections($formId);
        return $this->apiResponse(200, $result, '', '', []);
    }
}
