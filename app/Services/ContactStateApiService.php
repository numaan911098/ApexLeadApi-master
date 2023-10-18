<?php

namespace App\Services;

use GuzzleHttp\Client as HttpCient;
use Psr\Http\Message\ResponseInterface;
use Log;

class ContactStateApiService
{
    /**
     * Guzzle client instance.
     *
     * @var HttpCient
     */

    protected HttpCient $httpClient;
     /**
     * Constructor.
     *
     * @param HttpCient $httpClient
     */

    public function __construct(HttpCient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Add Cerificate
     *
     * @return ResponseInterface
     */

    public function getCertificate($claimUrl, $secretKey): ResponseInterface
    {
        $url = $claimUrl;

        $body = [
            'json' => ['secret_key' => $secretKey]
        ];

        try {
            return $this->httpClient->request(
                'post',
                $url,
                $body
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }
}
