<?php

namespace App\Listeners\Form;

use App\Events\FormCreated;
use App\Events\FormVariantCreated;
use App\Events\FormVariantUpdated;
use App\Services\PhishingFormService;

class DetectPhishingContent
{
    /**
     * @var PhishingFormService
     */
    private PhishingFormService $phishingFormService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(PhishingFormService $phishingFormService)
    {
        $this->phishingFormService = $phishingFormService;
    }

    /**
     * Handle the event.
     *
     * @param  FormCreated|FormVariantCreated|FormVariantUpdated  $event
     * @return void
     */
    public function handle($event)
    {
        $variant = $event->variant;

        $content = $this->phishingFormService->hasPhishingContent($variant);

        if ($content !== null) {
            $this->phishingFormService->reportPhishingContent($variant, $content);
        }
    }
}
