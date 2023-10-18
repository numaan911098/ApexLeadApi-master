<?php

namespace App\Policies;

use App\User;
use App\Form;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the form.
     *
     * @param  \App\User  $user
     * @param  \App\Form  $form
     * @return mixed
     */
    public function view(User $user, Form $form)
    {
        return $form->created_by === $user->id;
    }

    /**
     * Determine whether the user can create forms.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the form.
     *
     * @param  \App\User  $user
     * @param  \App\Form  $form
     * @return mixed
     */
    public function update(User $user, Form $form)
    {
        return $form->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the form.
     *
     * @param  \App\User  $user
     * @param  \App\Form  $form
     * @return mixed
     */
    public function delete(User $user, Form $form)
    {
        return $form->created_by === $user->id;
    }

    /**
     * Determine whether the user can reset the form status.
     *
     * @param  \App\User  $user
     * @param  \App\Form  $form
     * @return bool
     */
    public function resetFormStatus(User $user, Form $form)
    {
        return $form->created_by === $user->id;
    }

    /**
     * Determine whether the user can share the form between accounts.
     *
     * @param  \App\User  $user
     * @param  \App\Form  $form
     * @return mixed
     */
    public function share(User $user, Form $form)
    {
        return $user->isAdmin();
    }
}
