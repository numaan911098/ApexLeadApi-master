<?php

namespace App\Services;

use App\Enums\CachePrefixEnum;
use Http;
use Cache;

class GooglePlaceApiService
{
    /**
     * @var int|float
     */
    private int $decayMinutes = 10 * 60;

    /**
     * @link https://developers.google.com/maps/documentation/places/web-service/search?hl=id
     * @param string $apikey
     * @param string $input
     * @return array|null
     */
    public function searchPlace(string $apikey, string $input): ?array
    {
        if (empty($input)) {
            return null;
        }

        $cachePrefix = sprintf('%s_%s', CachePrefixEnum::GOOGLE_PLACE_API_SEARCH, $input);

        if (Cache::has($cachePrefix)) {
            return Cache::get($cachePrefix);
        }

        $endpoint = sprintf(
            'https://maps.googleapis.com/maps/api/place/autocomplete/json?%s',
            http_build_query([
                'input' => $input,
                'key' => $apikey,
            ])
        );

        try {
            $endpointResult = Http::get($endpoint)->json();

            Cache::put($cachePrefix, $endpointResult, $this->decayMinutes);
        } catch (\Exception $e) {
            return null;
        }

        return $endpointResult;
    }

    /**
     * @link https://developers.google.com/maps/documentation/places/web-service/details?hl=id
     * @param string $apikey
     * @param string $placeId
     * @return array|null
     */
    public function getPlaceDetail(string $apikey, string $placeId): ?array
    {
        if (empty($placeId)) {
            return null;
        }

        $cachePrefix = sprintf('%s_%s', CachePrefixEnum::GOOGLE_PLACE_API, $placeId);

        if (Cache::has($cachePrefix)) {
            return Cache::get($cachePrefix);
        }

        $endpoint = sprintf(
            'https://maps.googleapis.com/maps/api/place/details/json?%s',
            http_build_query([
                'key' => $apikey,
                'place_id' => $placeId,
                'fields' => 'address_component',
            ])
        );

        try {
            $endpointResult = Http::get($endpoint)->json();

            Cache::put($cachePrefix, $endpointResult, $this->decayMinutes);
        } catch (\Exception $e) {
            return null;
        }

        return $endpointResult;
    }
}
