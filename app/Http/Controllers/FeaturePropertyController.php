<?php

namespace App\Http\Controllers;

use App\Models\FeatureProperty;

class FeaturePropertyController extends Controller
{
  /**
     * FeatureProperty instance.
     *
     * @var FeatureProperty
     */
    protected $featurePropertyModel;

    /**
     * Constructor.
     *
     * @param FeatureProperty $featureProperty
     */
    public function __construct(
        FeatureProperty $featureProperty
    ) {
        $this->middleware('jwt.auth');
        $this->featurePropertyModel = $featureProperty;
    }

    /**
     * Get list of all available features.
     *
     * @return Response
     */
    public function index()
    {
        $allFeatureProperties = $this->featurePropertyModel->all()->toArray();
        return $this->apiResponse(200, $allFeatureProperties);
    }
}
