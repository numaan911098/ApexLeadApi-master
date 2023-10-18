<?php

namespace App\Services;

use App\Modules\Subscription\Contracts\SubscriptionHandlerInterface;
use App\Modules\Subscription\Handlers\PaddleSubscriptionHandler;
use Illuminate\Support\Facades\App;
use App\Plan;
use Sentry;

class SubscriptionService
{
    /**
     * @var Plan
     */
    protected $planModel;

    /**
     * @var PaddleSubscriptionHandler
     */
    protected $paddleSubscriptionHandler;

    /**
     * SubscriptionService constructor.
     * @param Plan $plan
     * @param PaddleSubscriptionHandler $handler
     */
    public function __construct(Plan $plan, PaddleSubscriptionHandler $handler)
    {
        $this->planModel = $plan;
        $this->paddleSubscriptionHandler = $handler;
    }

    /**
     * @param int $userId
     * @param int $subscriptionId
     * @return array|null
     */
    public function listPastDueUsers($userId, $subscriptionId): ?array
    {
        try {
            $handler = App::make(SubscriptionHandlerInterface::class, ['user_id' => $userId]);
            return $handler->getPastDueUser($subscriptionId);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * @param int $userId
     * @param int $subscriptionId
     * @return array|null
     */
    public function listUserPayment($userId, $subscriptionId): ?array
    {
        try {
            $handler = App::make(SubscriptionHandlerInterface::class, ['user_id' => $userId]);
            return $handler->listPayment($subscriptionId);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * @param int $userId
     * @param int $subscriptionId
     * @param int $paymentId
     * @return array|null
     */
    public function rescheduleUserPayment($userId, $subscriptionId, $paymentId): ?array
    {
        try {
            $handler = App::make(SubscriptionHandlerInterface::class, ['user_id' => $userId]);
            return $handler->reschedulePayment($subscriptionId, $paymentId);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * @return array|null
     */
    public function getDiscountCoupon(): ?array
    {
        $paddlePlanIds = $this->planModel
            ->whereNotNull('paddle_plan_id')
            ->where('paddle_plan_id', '<>', '')
            ->pluck('paddle_plan_id')->all();
        $planIds = implode(', ', $paddlePlanIds);
        try {
            return $this->paddleSubscriptionHandler->getDiscountCoupon($planIds);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * @param int $userId
     * @param int $subscriptionId
     * @return array|null
     */
    public function listUserTransaction($userId, $subscriptionId): ?array
    {
        try {
            $handler = App::make(SubscriptionHandlerInterface::class, ['user_id' => $userId]);
            return $handler->listTransaction($subscriptionId);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }
}
