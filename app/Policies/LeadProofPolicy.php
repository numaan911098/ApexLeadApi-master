<?php

namespace App\Policies;

use App\User;
use App\LeadProof;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadProofPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the leadProof.
     *
     * @param  \App\User  $user
     * @param  \App\LeadProof  $leadProof
     * @return mixed
     */
    public function view(User $user, LeadProof $leadProof)
    {
        return $user->id = $leadProof->formVariant->form->created_by;
    }

    /**
     * Determine whether the user can create leadProofs.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the leadProof.
     *
     * @param  \App\User  $user
     * @param  \App\LeadProof  $leadProof
     * @return mixed
     */
    public function update(User $user, LeadProof $leadProof)
    {
        return $user->id = $leadProof->formVariant->form->created_by;
    }

    /**
     * Determine whether the user can delete the leadProof.
     *
     * @param  \App\User  $user
     * @param  \App\LeadProof  $leadProof
     * @return mixed
     */
    public function delete(User $user, LeadProof $leadProof)
    {
        return $user->id = $leadProof->formVariant->form->created_by;
    }
}
