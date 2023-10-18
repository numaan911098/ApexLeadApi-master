<?php

namespace App\Services;

use App\Form;
use App\FormVariant;
use App\FormWebhook;
use App\FormWebhookRequest;
use App\Models\ContactState;
use Log;
use App\Modules\Security\Services\AuthService;
use App\Enums\FormConnectionsEnum;

class FormConnectionService
{
    public function __construct(
        FormWebhook $formWebhook,
        ContactState $contactState,
        Form $form,
        AuthService $authService
    ) {
        $this->formWebhook = $formWebhook;
        $this->contactState = $contactState;
        $this->form = $form;
        $this->authService = $authService;
    }

    public function getGlobalConnections(): array
    {
        $authUser = $this->authService->getUser();
        $forms = $this->form->where('created_by', $this->authService->getUserId())->get();
        $formsConnection = $this->form->getConnections($forms);
        if (!$authUser->canUseTrustedForm()) {
            $formsConnection = array_filter($formsConnection, function ($entry) {
                return $entry['type'] !== FormConnectionsEnum::TRUSTEDFORM;
            });
        }
        return  $formsConnection;
    }

    public function getConnections(int $formId): array
    {
        $authUser = $this->authService->getUser();
        $form = $this->form->where('id', $formId)->get();
        $formsConnection = $this->form->getConnections($form);
        if (!$authUser->canUseTrustedForm()) {
            $formsConnection = array_filter($formsConnection, function ($entry) {
                return $entry['type'] !== FormConnectionsEnum::TRUSTEDFORM;
            });
        }
        return $formsConnection;
    }
}
