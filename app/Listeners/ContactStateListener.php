<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\ContactStateApiService;
use App\FormLead;
use App\Models\ContactState;
use App\FormQuestionResponse;
use App\FormHiddenFieldResponse;
use App\Enums\QuestionTypesEnum;
use Log;

class ContactStateListener
{
    /**
     * ContactState instance.
     *
     * @var ContactStateApiService
     */
    // protected $contactState;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        ContactStateApiService $contactStateApiService,
        FormLead $formLead
    ) {
        $this->contactStateApiService = $contactStateApiService;
        $this->formLead = $formLead;
    }
     /**
      * Handle the event.
      *
      * @param  LeadCreated $event
      * @return void
      */
    public function handle(LeadCreated $event)
    {
        $claimUrl = $event->formLead->claim_url;
        $lead = $event->formLead;
        $form = $lead->form;
        if (isset($claimUrl) && $claimUrl !== '') {
            $data = ContactState::where('form_id', $form->id)
            ->where('form_variant_id', null)
            ->where('enable', true)
            ->first();
            if (!empty($data)) {
                $secretKey = $data->secret_key;
                $response = $this->contactStateApiService->getCertificate($claimUrl, $secretKey);
                $this->getResponse($response, $event);
            }

            $varaintData = ContactState::where('form_variant_id', $lead->form_variant_id)
            ->where('enable', true)
            ->first();
            if (!empty($varaintData)) {
                $secretKey = $varaintData->secret_key;
                $response = $this->contactStateApiService->getCertificate($claimUrl, $secretKey);
                $this->getResponse($response, $event);
            }
        }
    }
    public function getResponse($response, $event)
    {
        if ($response->getStatusCode() !== 201) {
            return;
        }

        $certificate = json_decode($response->getBody(), true);

        $certificateUrl = $certificate['cert_url'];

        $this->formLead::where(
            'id',
            $event->formLead->id
        )->update(['contactstate_cert_url' => $certificateUrl]);
    }
}
