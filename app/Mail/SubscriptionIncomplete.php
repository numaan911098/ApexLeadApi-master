<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Facades\App\Services\Util;
use App\User;

class SubscriptionIncomplete extends Mailable
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
     * Payment URL.
     *
     * @var string
     */
    public $paymentUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->paymentUrl = Util::config('leadgen.client_app_url') . '/plan';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.subscriptions.incomplete')
            ->subject('Payment Incomplete');
    }
}
