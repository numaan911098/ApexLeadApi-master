<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\UserEmailVerification;
use Mail;

class SendVerificationEmailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        if ($event->user->isEmailVerificationSent()) {
            Mail::to($event->user)
            ->send(new UserEmailVerification($event->user));
        }
    }
}
