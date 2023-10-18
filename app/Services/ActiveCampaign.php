<?php

namespace App\Services;

use GuzzleHttp\Client as HttpCient;
use Psr\Http\Message\ResponseInterface;
use App\user;
use Log;

class ActiveCampaign
{
    /**
     * Active Campaign API base URL.
     *
     * @var string
     */
    protected string $apiUrl = 'https://leadgenapp.api-us1.com/api/3/';

    /**
     * Active campaign API Token.
     *
     * @var string
     */
    protected ?string $apiToken;

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
        $this->apiToken = config('leadgen.active_campaign_api_token');
        $this->httpClient = $httpClient;
    }

    /**
     * Add contact
     *
     * @param User $user User model.
     * @return ResponseInterface
     */
    public function addContact(User $user): ResponseInterface
    {
        $url = $this->apiUrl . 'contacts';
        $body = [
            'headers' => [
                'Api-Token' => $this->apiToken,
            ],
            'json' => [
                'contact' => [
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'email' => $user->email,
                ],
            ],
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

    /**
     * Get list of contacts from active campaign.
     *
     * @param array $query Filter Contact list by query.
     * @return ResponseInterface
     */
    public function getContacts(array $query): ResponseInterface
    {
        $url = $this->apiUrl . 'contacts?' . http_build_query($query);

        $body = [
            'headers' => [
                'Api-Token' => $this->apiToken,
            ],
        ];

        try {
            return $this->httpClient->request(
                'get',
                $url,
                $body
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Apply tag to contact.
     *
     * @param integer $tagId Tag Id.
     * @param integer $contactId Contact Id.
     * @return ResponseInterface
     */
    public function applyTag(int $tagId, int $contactId): ResponseInterface
    {
        $url = $this->apiUrl . 'contactTags';

        $body = [
            'headers' => [
                'Api-Token' => $this->apiToken,
            ],
            'json' => [
                'contactTag' => [
                    'contact' => $contactId,
                    'tag' => $tagId,
                ],
            ],
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

    /**
     * Remove Tag by Contact Tag Id
     *
     * @param integer $contactTagId Relation between Tag & Contact.
     * @return ResponseInterface|null
     */
    public function removeTag(int $contactTagId): ?ResponseInterface
    {
        $url = $this->apiUrl . 'contactTags/' . $contactTagId;

        $body = [
            'headers' => [
                'Api-Token' => $this->apiToken,
            ],
        ];

        try {
            return $this->httpClient->request(
                'delete',
                $url,
                $body
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Get tags associated with a contact.
     *
     * @param integer $contactId Contact Id
     * @return ResponseInterface
     */
    public function contactTags(int $contactId): ResponseInterface
    {
        $url = $this->apiUrl . 'contacts/' . $contactId . '/contactTags';

        $body = [
            'headers' => [
                'Api-Token' => $this->apiToken,
            ],
        ];

        try {
            return $this->httpClient->request(
                'get',
                $url,
                $body
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Get Relation between Tag & Contact.
     *
     * @param integer $tagId
     * @param integer $contactId
     * @return integer|null
     */
    public function getContactTagId(int $tagId, int $contactId): ?int
    {
        $response = $this->contactTags($contactId);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $contactTags = json_decode($response->getBody(), true);

        if (empty($contactTags) || empty($contactTags['contactTags'])) {
            return null;
        }

        foreach ($contactTags['contactTags'] as $contactTag) {
            if ($tagId === intval($contactTag['tag'])) {
                return intval($contactTag['id']);
            }
        }

        return null;
    }
}
