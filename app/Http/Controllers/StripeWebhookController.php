<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Mail\SubscriptionSuccess;
use App\Mail\SubscriptionIncomplete;
use App\Enums\SubscriptionsEnum;
use App\Enums\StripePlansEnum;
use App\Events\ProUserRegistered;
use App\User;
use App\Plan;
use Log;
use Mail;
use DB;

class StripeWebhookController extends CashierController
{
    /**
     * Handle setup_intent succeeded.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleSetupIntentSucceeded($payload)
    {
        $paymentMethod = $payload['data']['object']['payment_method'];
        $metadata      = $payload['data']['object']['metadata'];

        if (!isset($metadata['user_id'])) {
            return;
        }

        $user = User::find($metadata['user_id']);

        if (empty($user)) {
            return;
        }

        if ($user->subscribed(SubscriptionsEnum::MAIN)) {
            return;
        }

        if (
            $user->subscription(SubscriptionsEnum::MAIN) &&
            ! $user->subscription(SubscriptionsEnum::MAIN)->hasIncompletePayment()
        ) {
            return;
        }

        $plan = Plan::where('public_id', StripePlansEnum::PRO)
            ->first();

        try {
            DB::beginTransaction();

            $coupon = !empty($metadata['coupon']) ? $metadata['coupon'] : '';

            $subscription = $user
                ->newSubscription(SubscriptionsEnum::MAIN, $plan->stripe_plan_id);

            if (!empty($coupon)) {
                $subscription = $subscription->withCoupon($coupon);
            }

            $subscription->create($paymentMethod);

            Mail::to($user)
                ->bcc([config('mail.from.address')])
                ->send(new SubscriptionSuccess($user));

            if (!$user->subscribe_newsletter) {
                $user->subscribe_newsletter = $metadata['subscribe_newsletter'] === 'true';
                $user->save();

                event(new ProUserRegistered($user));
            }

            DB::commit();
        } catch (IncompletePayment $exception) {
            DB::rollback();

            Mail::to($user)
                ->bcc([config('mail.from.address')])
                ->send(new SubscriptionIncomplete($user));
        }
    }
}
