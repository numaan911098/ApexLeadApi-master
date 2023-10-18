<?php

namespace App\Mail;

use App\Form;
use App\FormVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PhishingFormDetected extends Mailable
{
    use Queueable;
    use SerializesModels;

    public FormVariant $formVariant;

    public string $phishingContent;

    /**
     * Create a new message instance.
     *
     * @param FormVariant $formVariant
     * @param string $phishingContent
     */
    public function __construct(FormVariant $formVariant, string $phishingContent)
    {
        $this->formVariant = $formVariant;
        $this->phishingContent = $phishingContent;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.forms.phishing_content')
            ->replyTo($this->formVariant->form->createdBy->email)
            ->from(config('mail.from.address'), 'Leadgen Bot')
            ->subject('Phishing Form Detected');
    }
}
