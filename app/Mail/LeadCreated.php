<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\FormLead;
use App\User;
use Log;

class LeadCreated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $formLead;

    public $whitelabel;

    protected $fromName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(FormLead $formLead)
    {
        $this->formLead = FormLead::find($formLead->id);

        $form = $formLead->form;

        $this->whitelabel = $form->createdBy->whitelabel;

        $this->fromName = $form->formEmailNotification->from_name;
        if (empty($this->fromName)) {
            $this->fromName = config('mail.from.name');
        }

        $this->subject = $form->formEmailNotification->subject;
        if (empty($this->subject)) {
            $this->fromName = "Lead Created";
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.leads.created')
            ->replyTo($this->formLead->replyToEmailAddresses())
            ->from(config('mail.from.address'), $this->fromName)
            ->subject($this->subject);
    }
}
