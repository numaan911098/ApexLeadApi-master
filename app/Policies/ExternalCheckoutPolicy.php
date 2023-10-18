<?php

namespace App\Policies;

use App\User;
use App\ExternalCheckout;
use Illuminate\Auth\Access\HandlesAuthorization;
use Log;

class ExternalCheckoutPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the ExternalCheckout.
     *
     * @param  \App\User  $user
     * @param  \App\ExternalCheckout  $externalCheckout
     * @return mixed
     */
    public function view(User $user, ExternalCheckout $externalCheckout)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create ExternalCheckout.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the ExternalCheckout.
     *
     * @param  \App\User  $user
     * @param  \App\ExternalCheckout  $externalCheckout
     * @return mixed
     */
    public function update(User $user, ExternalCheckout $externalCheckout)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the ExternalCheckout.
     *
     * @param  \App\User  $user
     * @param  \App\ExternalCheckout  $externalCheckout
     * @return mixed
     */
    public function delete(User $user, ExternalCheckout $externalCheckout)
    {
        return $user->isAdmin();
    }
}
