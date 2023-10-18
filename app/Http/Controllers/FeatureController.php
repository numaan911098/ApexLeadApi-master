<?php

namespace App\Http\Controllers;

use App\Models\Feature;

class FeatureController extends Controller
{
  /**
     * Feature instance.
     *
     * @var Feature
     */
    protected $featureModel;

    /**
     * Constructor.
     *
     * @param Feature $feature
     */
    public function __construct(
        Feature $feature
    ) {
        $this->middleware('jwt.auth');
        $this->featureModel = $feature;
    }

    /**
     * Get list of all available features.
     *
     * @return Response
     */
    public function index()
    {
        $allFeatures = $this->featureModel->all()->toArray();
        return $this->apiResponse(200, $allFeatures);
    }
}
