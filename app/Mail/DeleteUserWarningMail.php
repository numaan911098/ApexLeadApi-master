<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeleteUserWarningMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Mail details
     * @var array
     */
    protected $details;

    /**
     * Create a new message instance.
     * @param array $details
     * @return void
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = $this->details['final'] ? 'Final warning: Your LeadGen App account will be deleted tomorrow'
        : 'Warning: Your Account will be deleted';
        return $this->markdown('emails.users.deleteUserWarningMail')
            ->subject($message)
            ->with('details', $this->details);
    }
}
