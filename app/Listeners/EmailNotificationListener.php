<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\LeadCreated as LeadCreatedMailable;
use Mail;
use Log;

class EmailNotificationListener
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
     * @param  LeadCreated  $event
     * @return void
     */
    public function handle(LeadCreated $event)
    {
        $lead = $event->formLead;
        $form = $lead->form;
        $setting = $form->formSetting;
        $formEmail = $form->formEmailNotification;

        if ($setting->email_notifications) {
            $mail = Mail::to($formEmail->toArr());

            if (!empty($formEmail->ccArr())) {
                $mail->cc($formEmail->ccArr());
            }

            if (!empty($formEmail->bccArr())) {
                $mail->bcc($formEmail->bccArr());
            }

            $mail->queue(new LeadCreatedMailable($lead));
        }
    }
}
