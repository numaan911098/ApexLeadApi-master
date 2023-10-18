<?php

namespace App\Modules\Subscription;

use App\Modules\Subscription\Contracts\SubscriptionHandlerInterface;
use App\Modules\Base\BaseManager;
use App\Enums\ErrorTypesEnum;
use App\Enums\Paddle\PaddleSubscriptionStatusEnum;
use Stripe\Stripe;
use App\PaddleSubscription;
use Auth;
use Log;

class SubscriptionManager extends BaseManager
{
    protected $subscriptionHandler;
    public function __construct(SubscriptionHandlerInterface $handler = null)
    {
        $this->subscriptionHandler = $handler;
    }

    public function pauseSubscription($id)
    {
        $user = Auth::user();
        $subscription = $user->getSubscription();

        if ($subscription->id !== intval($id)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Subscription Id not found.',
            ])->response();
        }

        if ($subscription->status === PaddleSubscriptionStatusEnum::PAUSED) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_ALREADY_PAUSED,
                'error_message' => 'Subscription is already in paused state.',
            ])->response();
        }

        if ($subscription->user_id !== $user->id) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'No record found for the subscription.',
            ])->response();
        }

        if (!$this->subscriptionHandler->pause($subscription->paddle_id)) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_PAUSE_FAILED,
                'error_message' => 'Unable to pause subscription.',
            ])->response();
        }

        return $this->response();
    }

    public function resumeSubscription($id)
    {
        $user = Auth::user();
        $subscription = $user->getSubscription();

        if ($subscription->id !== intval($id)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Subscription Id not found.',
            ])->response();
        }

        if ($subscription->status !== PaddleSubscriptionStatusEnum::PAUSED) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_ALREADY_RESUMED,
                'error_message' => 'Subscription is already resumed.',
            ])->response();
        }

        if ($subscription->user_id !== $user->id) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'No record found for the subscription.',
            ])->response();
        }

        if (!$this->subscriptionHandler->resume($subscription->paddle_id)) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_RESUME_FAILED,
                'error_message' => 'Unable to resume subscription.',
            ])->response();
        }

        return $this->response();
    }

    public function cancelSubscription($id)
    {
        $user = Auth::user();
        $subscription = $user->getSubscription();

        if ($subscription->id !== intval($id)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Subscription Id not found.',
            ])->response();
        }

        if ($subscription->status === PaddleSubscriptionStatusEnum::DELETED) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_ALREADY_CANCELLED,
                'error_message' => 'Subscription is already in cancel state.',
            ])->response();
        }

        if ($subscription->user_id !== $user->id) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'No record found for the subscription.',
            ])->response();
        }

        if (!$this->subscriptionHandler->cancel($subscription->paddle_id)) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_CANCEL_FAILED,
                'error_message' => 'Unable to cancel subscription.',
            ])->response();
        }

        return $this->response();
    }

    public function updateSubscription($id, $plan)
    {
        $user = Auth::user();
        $subscription = $user->getSubscription();

        if ($subscription->id !== intval($id)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Subscription Id not found.',
            ])->response();
        }

        if ($subscription->user_id !== $user->id) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'No record found for the subscription.',
            ])->response();
        }

        if (!$this->subscriptionHandler->update($subscription->paddle_id, $plan)) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::SUBSCRIPTION_UPDATE_FAILED,
                'error_message' => 'Unable to update subscription.',
            ])->response();
        }

        return $this->response();
    }
}
