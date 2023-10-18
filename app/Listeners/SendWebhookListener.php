<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Facades\App\Services\Util;
use App\Enums\DashboardsEnum;
use App\FormWebhook;
use App\FormQuestionResponse;
use App\FormHiddenFieldResponse;
use Log;

class SendWebhookListener
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

        $questionResponses = FormQuestionResponse::where('form_lead_id', $lead->id)
            ->get();

        $hiddenFieldResponses = FormHiddenFieldResponse::where('form_lead_id', $lead->id)
            ->get();

        // send variant webhooks
        $webhooks = FormWebhook::where('form_variant_id', $lead->form_variant_id)
            ->where('enable', true)
            ->get();
        foreach ($webhooks as $webhook) {
            Util::sendVariantWebhook($webhook, $questionResponses, $hiddenFieldResponses, $lead);
        }

        // send global webhooks
        $webhooks = FormWebhook::where('form_id', $form->id)
            ->where('form_variant_id', null)
            ->where('enable', true)
            ->get();
        foreach ($webhooks as $webhook) {
            Util::sendGlobalWebhook($webhook, $form, $questionResponses, $hiddenFieldResponses, $lead);
        }
    }
}
