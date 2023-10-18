<?php

namespace App\Policies;

use App\Models\FormTemplate;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormTemplatePolicy
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
     * @param  \App\Models\FormTemplate  $formTemplate
     * @return mixed
     */
    public function view(User $user, FormTemplate $formTemplate)
    {
        return $user->isAdmin();
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
     * @param  \App\Models\FormTemplate  $formTemplate
     * @return mixed
     */
    public function update(User $user, FormTemplate $formTemplate)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\FormTemplate  $formTemplate
     * @return mixed
     */
    public function delete(User $user, FormTemplate $formTemplate)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\FormTemplate  $formTemplate
     * @return mixed
     */
    public function restore(User $user, FormTemplate $formTemplate)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\FormTemplate  $formTemplate
     * @return mixed
     */
    public function forceDelete(User $user, FormTemplate $formTemplate)
    {
        //
    }
}
