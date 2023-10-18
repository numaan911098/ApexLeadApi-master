<?php

namespace App\Policies;

use App\User;
use App\GoogleRecaptchaKey;
use Illuminate\Auth\Access\HandlesAuthorization;
use Log;

class GoogleRecaptchaKeyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the googleRecaptchaKey.
     *
     * @param  \App\User  $user
     * @param  \App\GoogleRecaptchaKey  $googleRecaptchaKey
     * @return mixed
     */
    public function view(User $user, GoogleRecaptchaKey $googleRecaptchaKey)
    {
        return $user->id === $googleRecaptchaKey->created_by;
    }

    /**
     * Determine whether the user can create googleRecaptchaKeys.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the googleRecaptchaKey.
     *
     * @param  \App\User  $user
     * @param  \App\GoogleRecaptchaKey  $googleRecaptchaKey
     * @return mixed
     */
    public function update(User $user, GoogleRecaptchaKey $googleRecaptchaKey)
    {
        return $user->id === $googleRecaptchaKey->created_by;
    }

    /**
     * Determine whether the user can delete the googleRecaptchaKey.
     *
     * @param  \App\User  $user
     * @param  \App\GoogleRecaptchaKey  $googleRecaptchaKey
     * @return mixed
     */
    public function delete(User $user, GoogleRecaptchaKey $googleRecaptchaKey)
    {
        return $user->id === $googleRecaptchaKey->created_by;
    }
}
