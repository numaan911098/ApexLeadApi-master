<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LandingPageTemplate;

class LandingPageTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function index()
    {
        return $this->apiResponse(200, LandingPageTemplate::all()->toArray());
    }
}
