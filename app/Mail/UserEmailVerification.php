<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Models\UserSettings;
use Facades\App\Services\Util;

class UserEmailVerification extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * $verificationLink
     *
     * @var string
     */
    public $verifyLink;


    public $user;

    protected $userSettingsModel;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->verifyLink = sprintf(
            '%sverify-email/%s/%s',
            Util::config('leadgen.client_app_url'),
            $user->id,
            $user->verification_token,
        );

        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */

    public function build()
    {
        $message = $this->user->isEmailVerificationNeeded()
        ? 'Activate your LeadGen App account'
        : 'User Email Verification';
        return $this->markdown('emails.users.verification')
        ->subject($message);
    }
}
