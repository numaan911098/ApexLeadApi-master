<?php

namespace App\Policies;

use App\Models\Credential;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CredentialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Credential  $credential
     * @return mixed
     */
    public function view(User $user, Credential $credential)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Credential  $credential
     * @return mixed
     */
    public function update(User $user, Credential $credential)
    {
        return $credential->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Credential  $credential
     * @return mixed
     */
    public function delete(User $user, Credential $credential)
    {
        return $credential->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Credential  $credential
     * @return mixed
     */
    public function restore(User $user, Credential $credential)
    {
        return $credential->created_by === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Credential  $credential
     * @return mixed
     */
    public function forceDelete(User $user, Credential $credential)
    {
        return $credential->created_by === $user->id;
    }
}
