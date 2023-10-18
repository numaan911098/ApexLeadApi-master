<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function apiResponse(
        int $code = 200,
        array $data = [],
        string $error_type = "",
        string $error_message = "",
        array $errors = [],
        array $pagination = []
    ) {
        $response = [
            'meta' => [
                'code' => $code
            ]
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        if (!empty($error_type)) {
            $response['meta']['error_type'] = $error_type;
        }

        if (!empty($error_message)) {
            $response['meta']['error_message'] = $error_message;
        }

        if (!empty($errors)) {
            $response['meta']['errors'] = $errors;
        }

        if (!empty($pagination)) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }

    public function managerResponse(array $response)
    {
        return response()->json($response, $response['meta']['code']);
    }

    /*public function apiAuthorize($permission, $model)
    {
        try {
            $this->authorize($permission, $model);
        } catch (\Exception $e)
        {
            return response()->json([
                'meta' => [
                    'code' => '403',
                    'error_message' => 'You are not Authorized for this action'
                ]
            ], 403);
        }
    }*/
}
