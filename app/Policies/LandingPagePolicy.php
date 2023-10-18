<?php

namespace App\Policies;

use App\User;
use App\LandingPage;
use Illuminate\Auth\Access\HandlesAuthorization;

class LandingPagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the landingPage.
     *
     * @param  \App\User  $user
     * @param  \App\LandingPage  $landingPage
     * @return mixed
     */
    public function view(User $user, LandingPage $landingPage)
    {
        return $landingPage->created_by === $user->id;
    }

    /**
     * Determine whether the user can create landingPages.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the landingPage.
     *
     * @param  \App\User  $user
     * @param  \App\LandingPage  $landingPage
     * @return mixed
     */
    public function update(User $user, LandingPage $landingPage)
    {
        return $landingPage->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the landingPage.
     *
     * @param  \App\User  $user
     * @param  \App\LandingPage  $landingPage
     * @return mixed
     */
    public function delete(User $user, LandingPage $landingPage)
    {
        return $landingPage->created_by === $user->id;
    }
}
