<?php

namespace App\Http\Controllers;

use App\Services\FormLeadViewService;
use Illuminate\Http\Request;

class FormLeadViewController extends Controller
{

    protected FormLeadViewService $formLeadViewService;

    public function __construct(FormLeadViewService $formLeadViewService)
    {
        $this->formLeadViewService = $formLeadViewService;
    }

    public function markRead(Request $request)
    {
        if ($request->has('formId')) {
            $formIds = json_decode($request->formId, true);
            $result = $this->formLeadViewService->markViewed($formIds, null);
        } else {
            $formVariantId = $request->formVariantId;
            $result = $this->formLeadViewService->markViewed(null, $formVariantId);
        }
        return $this->apiResponse(200, $result);
    }
}
