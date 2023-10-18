<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\InactiveUser;
use App\Enums\RequestSourceEnum;
use Sentry;

class RemoveFromInactiveAccounts
{
    /**
     * @var InactiveUser
     */
    protected $inactiveUserModel;

    /**
     * Create the event listener.
     * @param InactiveUser $inactiveUser
     * @return void
     */
    public function __construct(InactiveUser $inactiveUser)
    {
        $this->inactiveUserModel = $inactiveUser;
    }

    /**
     * Handle the event.
     *
     * @param  UserLoggedIn  $event
     * @return void
     */
    public function handle(UserLoggedIn $event)
    {
        try {
            $this->inactiveUserModel->where('user_id', $event->user->id)
                ->where('delete_source', RequestSourceEnum::SOURCE_COMMAND)->delete();
        } catch (\Exception $e) {
            Sentry\captureException($e);
        }
    }
}
