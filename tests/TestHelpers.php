<?php

namespace Tests;

use JWTAuth;

trait TestHelpers
{
    public function apiHeaders(
        array $headers = [],
        $token = null,
        \App\User $user = null
    ) {
        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        } elseif (!empty($user)) {
            $token = JWTAuth::fromUser($user);
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }
}
