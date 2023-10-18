<?php

namespace App\Listeners\Paddle;

use Exception;
use ProtoneMedia\LaravelPaddle\Events\SubscriptionCreated as SubscriptionCreatedEvent;
use App\Listeners\Paddle\BaseListener;
use App\Events\ProUserRegistered;
use App\Events\ProTrialUserRegistered;
use App\Events\ScaleUserRegistered;
use App\PaddleSubscription;
use App\Plan;
use App\Enums\Paddle\PaddleAlertTypesEnum;
use App\Enums\PlansEnum;

class SubscriptionCreated extends BaseListener
{
    /**
     * Handle the event.
     *
     * @param  SubscriptionCreatedEvent  $event
     * @return void
     */
    public function handle(SubscriptionCreatedEvent $event)
    {
        if (!$this->validateWebhook($event, PaddleAlertTypesEnum::SUBSCRIPTION_CREATED)) {
            return;
        }

        if (!empty($this->user->getPaddleSubscription())) {
            return;
        }

        PaddleSubscription::create([
            'user_id' => $this->user->id,
            'paddle_id' => $event->subscription_id,
            'paddle_plan' => $event->subscription_plan_id,
            'quantity' => $event->quantity,
            'ends_at' => !empty($event->cancellation_effective_date) ? $event->cancellation_effective_date : null,
            'status' => $event->status,
            'next_bill_date' => $event->next_bill_date,
            'currency' => $event->currency
        ]);

        $this->user->paddle_id = $event->user_id;
        $this->user->default_plan_id = Plan::where('public_id', PlansEnum::FREE)->first()->id;
        $this->user->save();

        try {
            if ($event->subscription_plan_id === config('leadgen.paddle_pro_plan_id')) {
                event(new ProUserRegistered($this->user));
            }
            if ($event->subscription_plan_id === config('leadgen.paddle_pro_trial_plan_id')) {
                event(new ProTrialUserRegistered($this->user));
            }
            if ($event->subscription_plan_id === config('leadgen.paddle_scale_plan_id')) {
                event(new ScaleUserRegistered($this->user));
            }
        } catch (\Exception $e) {
            app('sentry')->captureException($e);
        }
    }
}
