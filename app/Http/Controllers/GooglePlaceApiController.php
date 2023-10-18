<?php

namespace App\Http\Controllers;

use App\Services\GooglePlaceApiService;
use Illuminate\Http\Request;

class GooglePlaceApiController extends Controller
{
    /**
     * @var GooglePlaceApiService Service instance.
     */
    private GooglePlaceApiService $googlePlaceApiService;

    /**
     * GooglePlaceApiController constructor.
     * @param GooglePlaceApiService $googlePlaceApiService
     */
    public function __construct(GooglePlaceApiService $googlePlaceApiService)
    {
        $this->googlePlaceApiService = $googlePlaceApiService;
    }

    /**
     * @param string $apikey
     * @param string $input
     * @return mixed
     */
    public function searchPlace(string $apikey, string $input)
    {
        $result = $this->googlePlaceApiService->searchPlace($apikey, $input);

        return $this->apiResponse(200, $result);
    }

    /**
     * @param string $apikey
     * @param string $placeId
     * @return mixed
     */
    public function getPlaceDetail(string $apikey, string $placeId)
    {
        $result = $this->googlePlaceApiService->getPlaceDetail($apikey, $placeId);

        return $this->apiResponse(200, $result);
    }
}
