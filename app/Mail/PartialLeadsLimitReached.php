<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartialLeadsLimitReached extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Reset periods and ueser name.
     *
     * @var array
     */
    public $details;

    /**
     * Create a new message instance.
     *
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
        $message = 'Quota Exceeded for Partial Leads' . ($this->details['resetPeriod'] !== 'NONE' ? ': Upgrade Now'
        : '');

        return $this->markdown('emails.leads.partialLeadsLimitReached')
            ->subject($message)
            ->with('details', $this->details);
    }
}
