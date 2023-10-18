<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Facades\App\Services\Util;
use App\User;

class SubscriptionSuccess extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * User instance
     *
     * @var User
     */
    public $user;

    /**
     * Client URL.
     *
     * @var string
     */
    public $appUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->appUrl = Util::config('leadgen.client_app_url');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.subscriptions.success')
        ->subject('Payment Successfull');
    }
}
