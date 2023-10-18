<?php

namespace App\Listeners\Paddle;

use Exception;
use ProtoneMedia\LaravelPaddle\Events\SubscriptionCancelled as SubscriptionCancelledEvent;
use App\Listeners\Paddle\BaseListener;
use App\PaddleSubscription;
use App\User;
use App\Plan;
use App\Enums\Paddle\PaddleAlertTypesEnum;
use App\Events\ProTrialUserRegistered;
use App\Events\ProTrialUserCancelled;
use Log;

class SubscriptionCancelled extends BaseListener
{
    /**
     * Handle the event.
     *
     * @param  SubscriptionCancelledEvent  $event
     * @return void
     */
    public function handle(SubscriptionCancelledEvent $event)
    {
        if (!$this->validateWebhook($event, PaddleAlertTypesEnum::SUBSCRIPTION_CANCELLED)) {
            return;
        }

        $subscription = $this->user->getPaddleSubscription();

        if (empty($subscription)) {
            return;
        }

        if ($subscription->paddle_id !== $event->subscription_id) {
            return;
        }

        if ($this->user->paddle_id !== $event->user_id) {
            return;
        }

        $subscription->paused_at = null;
        $subscription->paused_reason = null;
        $subscription->paused_from = null;
        $subscription->status = $event->status;
        $subscription->next_bill_date = null;
        $subscription->currency = $event->currency;
        $subscription->ends_at = !empty(
            $event->cancellation_effective_date
        ) ? $event->cancellation_effective_date : null;
        $subscription->save();
        try {
            if ($event->subscription_plan_id === config('leadgen.paddle_pro_trial_plan_id')) {
                if (!empty($subscription->ends_at)) {
                    event(new ProTrialUserCancelled($this->user));
                } else {
                    event(new ProTrialUserRegistered($this->user));
                }
            }
        } catch (\Exception $e) {
            app('sentry')->captureException($e);
        }
    }
}
