<?php

namespace App\Modules\Security\Services;

use App\User;
use Auth;

class AuthService
{
    /**
     * Get currently authenticated user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return Auth::user();
    }

    /**
     * Get currently authenticated user.
     *
     * @return User
     */
    public function getUserId(): int
    {
        return $this->getUser()->id;
    }
}
