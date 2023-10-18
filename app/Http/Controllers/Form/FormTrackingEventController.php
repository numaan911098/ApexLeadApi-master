<?php

namespace App\Http\Controllers\Form;

use App\Form;
use App\Http\Controllers\Controller;
use App\Http\Requests\Form\FormTrackingEvent\UpdateFormTrackingEventRequest;
use App\Models\Form\FormTrackingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Security\Services\AuthService;
use App\Enums\ErrorTypesEnum;
use App\Enums\Form\FormTrackingEventTypesEnum;

class FormTrackingEventController extends Controller
{
    /**
     * @var AuthService
     */
    protected $authService;
    /**
     * @var FormTrackingEvent
     */
    private FormTrackingEvent $formTrackingEvent;

    /**
     * @param FormTrackingEvent $formTrackingEvent
     * @param AuthService  $authService
     * @return void
     */
    public function __construct(FormTrackingEvent $formTrackingEvent, AuthService $authService)
    {
        $this->middleware('jwt.auth');

        $this->formTrackingEvent = $formTrackingEvent;
        $this->authService = $authService;
    }

    /**
     * @param Form $form
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getEvents(Form $form): JsonResponse
    {
        $this->authorize('view', $form);

        $events = $this->formTrackingEvent->getEvents($form)->toArray();

        return $this->apiResponse(200, $events);
    }

    /**
     * @param UpdateFormTrackingEventRequest $request
     * @param FormTrackingEvent $formTrackingEvent
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(UpdateFormTrackingEventRequest $request, FormTrackingEvent $formTrackingEvent): JsonResponse
    {
        $this->authorize('update', $formTrackingEvent->form);

        $authUser = $this->authService->getUser();
        if ($request->input('title') === FormTrackingEventTypesEnum::TRUSTEDFORM) {
            if (!$authUser->canUseTrustedForm()) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypesEnum::INAPPROPRIATE_PLAN,
                    'Please upgrade to Enterprise in order to use this Integration.'
                );
            }
        }
        if ($request->input('title') !== FormTrackingEventTypesEnum::TRUSTEDFORM) {
            if (strpos($request->input('script'), 'api.trustedform.com') !== false) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypesEnum::TRUSTEDFORM_SCRIPT,
                    'TrustedForm script not allowed.'
                );
            }
        }
        $formTrackingEvent->script = $request->input('script');
        $formTrackingEvent->active = $request->input('active');
        $formTrackingEvent->configured = !empty(trim($formTrackingEvent->script));
        $formTrackingEvent->save();

        return $this->apiResponse(200, $formTrackingEvent->toArray());
    }
}
