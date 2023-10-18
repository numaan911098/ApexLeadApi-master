<?php

namespace App\Modules\Subscription\Handlers;

use App\Enums\Paddle\PaddleAlertTypesEnum;
use App\Modules\Subscription\Contracts\SubscriptionHandlerInterface;
use ProtoneMedia\LaravelPaddle\Paddle;
use Carbon\Carbon;

class PaddleSubscriptionHandler implements SubscriptionHandlerInterface
{
    /**
     * Pause current subscription.
     *
     * @param integer $id Subscription id.
     */
    public function pause($id)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'subscription_id' => (int) $id,
            'pause' => true,
        ];

        Paddle::subscription()->updateUser($payload)->send();

        return true;
    }

    /**
     * Resume paused subscription.
     *
     * @param integer $id Subscription id.
     */
    public function resume($id)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'subscription_id' => (int) $id,
            'pause' => false,
        ];

        Paddle::subscription()->updateUser($payload)->send();

        return true;
    }

    /**
     * Cancel subscription.
     *
     * @param integer $id Subscription id.
     */
    public function cancel($id)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'subscription_id' => (int) $id,
        ];

        Paddle::subscription()->cancelUser($payload)->send();

        return true;
    }

    /**
     * Update current subscription.
     *
     * @param integer $id Subscription id.
     */
    public function update($id, $plan)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'subscription_id' => (int) $id,
            'plan_id' => (int) $plan,
        ];

        Paddle::subscription()->updateUser($payload)->send();

        return true;
    }

    /**
     * past due subscription details.
     *
     * @param integer $id Subscription id.
     */
    public function getPastDueUser($id)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'subscription_id' => (int) $id,
        ];

        return Paddle::subscription()->listUsers($payload)->send();
    }

    /**
     * list payment
     * @param integer $id Subscription id
     */
    public function listPayment($id)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'subscription_id' => (int) $id,
        ];

        return Paddle::subscription()->listPayments($payload)->send();
    }

    /**
     * reschedule subscription
     * @param integer $id Subscription id
     * @param integer $id payment id
     */
    public function reschedulePayment($id, $paymentId)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'payment_id' => (int) $paymentId,
            'date' => Carbon::now()->format('Y-m-d'),
        ];

        return Paddle::subscription()->reschedulePayment($payload)->send();
    }

    /**
     * list coupon for all plans
     * @param integer $id plan ids
     */
    public function getDiscountCoupon($planIds)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
            'product_id' => (int) $planIds,
        ];

        return Paddle::product()->listCoupons($payload)->send();
    }

    /**
     * list transaction
     * @param integer $id Subscription id
     */
    public function listTransaction($id)
    {
        $payload = [
            'vendor_id' => (int) config('leadgen.paddle_vendor_id'),
            'vendor_auth_code' => config('leadgen.paddle_vendor_auth_code'),
        ];

        $entity = PaddleAlertTypesEnum::SUBSCRIPTION;
        $subscriptionId = (int) $id;

        return Paddle::product()->listTransactions($entity, $subscriptionId, $payload)->send();
    }
}
