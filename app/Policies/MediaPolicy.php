<?php

namespace App\Policies;

use App\User;
use App\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the media.
     *
     * @param  \App\User  $user
     * @param  \App\Media  $media
     * @return mixed
     */
    public function view(User $user, Media $media)
    {
        if ($media->public) {
            return true;
        }

        return $user->id === $media->uploaded_by;
    }

    /**
     * Determine whether the user can create media.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the media.
     *
     * @param  \App\User  $user
     * @param  \App\Media  $media
     * @return mixed
     */
    public function update(User $user, Media $media)
    {
        return $user->id === $media->uploaded_by;
    }

    /**
     * Determine whether the user can delete the media.
     *
     * @param  \App\User  $user
     * @param  \App\Media  $media
     * @return mixed
     */
    public function delete(User $user, Media $media)
    {
        return $user->id === $media->uploaded_by;
    }
}
