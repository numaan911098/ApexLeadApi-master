<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LandingPageVisit;
use App\LandingPage;
use App\Visitor;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use Agent;
use Validator;

class LandingPageVisitController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth')->except(['store']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'landing_page_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorType::INVALID_DATA,
                'landing_page_id field is required'
            );
        }

        $landingpage = LandingPage::findOrFail($request->input('landing_page_id'));

        if ($request->filled('leadgen_visitor_id')) {
            $visitor = Visitor::where(
                'ref_id',
                $request->input('leadgen_visitor_id')
            )->first();
        } else {
            $visitor = Visitor::create([
                'ref_id' => Util::uuid4()
            ]);
        }

        $visit = LandingPageVisit::create([
            'landing_page_id' => $landingpage->id,
            'visitor_id' => $visitor->id,
            'os' => Agent::platform(),
            'device_type' => Util::deviceType(),
            'device_name' => Agent::device(),
            'robot_name' => Agent::robot(),
            'is_robot' => Agent::isRobot(),
            'browser' => Agent::browser(),
            'source_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);

        $visit->load('visitor');

        return $this->apiResponse(200, $visit->toArray());
    }
}
