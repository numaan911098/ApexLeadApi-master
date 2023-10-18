<?php

namespace App\Modules\Base;

/**
 * Base manager class
 */
abstract class BaseManager
{

    /**
     * Manager response array.
     *
     * @access protected
     *
     * @var array
     */
    protected $response;
/**
     * Base manager constructor.
     */
    public function __construct()
    {
        $this->response = [];
    }

    /**
     * Manager response.
     *
     * @access protected
     *
     * @return array
     */
    protected function response()
    {
        $response = [
            'meta' => [
                'code' => empty($this->response['code']) ? 200 : $this->response['code'],
            ]
        ];
        if (!empty($this->response['data'])) {
            $response['data'] = $this->response['data'];
        }

        if (!empty($this->response['error_type'])) {
            $response['meta']['error_type'] = $this->response['error_type'];
        }

        if (!empty($this->response['error_message'])) {
            $response['meta']['error_message'] = $this->response['error_message'];
        }

        if (!empty($this->response['errors'])) {
            $response['meta']['errors'] = $this->response['errors'];
        }

        if (!empty($this->response['pagination'])) {
            $response['pagination'] = $this->response['pagination'];
        }

        return $response;
    }

    /**
     * Add response parameters.
     *
     * @access protected
     *
     * @param string $key Parameter key.
     * @param mixed  $value Parameter value.
     * @return mixed
     */
    protected function addResponse($key, $value)
    {
        $this->response[$key] = $value;
        return $this;
    }

    /**
     * Fill multiple response parameters.
     *
     * @param array $responses Key-value pair of response parameters.
     * @return mixed
     */
    protected function fillResponse(array $responses)
    {
        foreach ($responses as $key => $value) {
            $this->addResponse($key, $value);
        }

        return $this;
    }
}
