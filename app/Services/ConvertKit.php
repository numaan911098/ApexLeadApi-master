<?php

namespace App\Services;

use GuzzleHttp\Client as HttpCient;
use Log;

class ConvertKit
{

    /**
     * ConvertKit API base URL.
     *
     * @var string
     */
    protected $apiUrl = 'https://api.convertkit.com/v3';
/**
     * ConvertKit account API key.
     *
     * @var string
     */
    protected $apiKey;
/**
     * ConvertKit account API secret key.
     *
     * @var [type]
     */
    protected $apiSecretKey;
/**
     * Guzzle client instance.
     *
     * @var HttpCient
     */
    protected $httpClient;
/**
     * Constructor.
     *
     * @param HttpCient $httpClient
     */
    public function __construct(HttpCient $httpClient)
    {
        $this->apiKey = config('leadgen.convert_kit_api_key');
        $this->apiSecretKey = config('leadgen.convert_kit_api_secret');
        $this->httpClient = $httpClient;
    }

    /**
     * Add subscriber to sequence.
     *
     * @param int $sequenceId
     * @param array $subscriber
     * @return void
     */
    public function addSubscriberToSequence($sequenceId, array $subscriber)
    {
        $url = $this->apiUrl . '/courses/' . $sequenceId . '/subscribe';
        $body = [
            'json' => array_merge(['api_key' => $this->apiKey], $subscriber)
        ];
        try {
            return $this->httpClient->request('post', $url, $body);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function tagSubscriber($tagId, $email)
    {
        $url = $this->apiUrl . '/tags/' . $tagId . '/subscribe';
        $body = [
            'json' => array_merge(['api_key' => $this->apiKey, 'email' => $email])
        ];
        try {
            return $this->httpClient->request('post', $url, $body);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }
}
