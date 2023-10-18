<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadLimitReached extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Mail details
     * @var array
     */
    public $details;

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
        $message = ($this->details['threshHold'] === 100) ? 'Quota Exceeded for Leads' : 'Upgrade Now';

        return $this->markdown('emails.leads.leadLimitReached')
            ->subject($message)
            ->with('details', $this->details);
    }
}
