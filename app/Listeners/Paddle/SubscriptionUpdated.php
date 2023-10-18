<?php

namespace App\Listeners\Paddle;

use Exception;
use ProtoneMedia\LaravelPaddle\Events\SubscriptionUpdated as SubscriptionUpdatedEvent;
use App\Listeners\Paddle\BaseListener;
use App\PaddleSubscription;
use App\User;
use App\Plan;
use App\Enums\Paddle\PaddleAlertTypesEnum;
use App\Enums\Paddle\PaddleSubscriptionStatusEnum;
use App\Events\ProUserCancelled;
use App\Events\ProTrialUserCancelled;
use App\Events\ScaleUserCancelled;
use App\Events\ProUserRegistered;
use App\Events\ProTrialUserRegistered;
use App\Events\ScaleUserRegistered;
use Log;

class SubscriptionUpdated extends BaseListener
{
    /**
     * Handle the event.
     *
     * @param  SubscriptionUpdatedEvent  $event
     * @return void
     */
    public function handle(SubscriptionUpdatedEvent $event)
    {
        if (!$this->validateWebhook($event, PaddleAlertTypesEnum::SUBSCRIPTION_UPDATED)) {
            return;
        }

        $subscription = $subscription = $this->user->getPaddleSubscription();

        if (empty($subscription)) {
            return;
        }

        if ($subscription->paddle_id !== $event->subscription_id) {
            return;
        }

        if ($this->user->paddle_id !== $event->user_id) {
            return;
        }

        if (!empty($event->paused_at)) {
            $subscription->status = PaddleSubscriptionStatusEnum::PAUSED;
            $subscription->paused_at = $event->paused_at;
            $subscription->paused_reason = $event->paused_reason;
            $subscription->paused_from = $event->paused_from;
        } else {
            $subscription->status = $event->status;
            $subscription->paused_at = null;
            $subscription->paused_reason = null;
            $subscription->paused_from = null;
        }

        $subscription->ends_at = null;
        $subscription->paddle_plan = $event->subscription_plan_id;
        $subscription->quantity = $event->new_quantity;
        $subscription->next_bill_date = $event->next_bill_date;
        $subscription->currency = $event->currency;
        $subscription->save();

        try {
            if ($event->subscription_plan_id === config('leadgen.paddle_pro_plan_id')) {
                if (!empty($event->paused_at)) {
                    event(new ProUserCancelled($this->user));
                } else {
                    event(new ProUserRegistered($this->user));
                }
            }
            if ($event->subscription_plan_id === config('leadgen.paddle_scale_plan_id')) {
                if (!empty($event->paused_at)) {
                    event(new ScaleUserCancelled($this->user));
                } else {
                    event(new ScaleUserRegistered($this->user));
                }
            }
            if ($event->subscription_plan_id === config('leadgen.paddle_pro_trial_plan_id')) {
                if (!empty($event->paused_at)) {
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
