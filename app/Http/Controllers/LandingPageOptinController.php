<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreLandingPageOptinRequest;
use App\LandingPage;
use App\LandingPageVisit;
use App\LandingPageOptin;

class LandingPageOptinController extends Controller
{
    public function store(StoreLandingPageOptinRequest $request)
    {
        $visit = LandingPageVisit::findOrFail($request->input('landing_page_visit_id'));
        $leadId = null;
        if ($request->filled('form_lead_id')) {
            $leadId = $request->input('form_lead_id');
        }
        $optin = LandingPageOptin::create([
            'landing_page_visit_id' => $visit->id,
            'visitor_id' => $visit->visitor_id,
            'form_lead_id' => $leadId,
            'landing_page_id' => $request->input('landing_page_id')
        ]);
        return $this->apiResponse(200, $optin->toArray());
    }
}
