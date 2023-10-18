<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Subscription\SubscriptionManager;
use App\Enums\ErrorTypesEnum;
use App\Enums\DiscountTypeEnum;
use App\Services\SubscriptionService;
use App\Modules\Security\Services\AuthService;

class SubscriptionController extends Controller
{
    /**
     * Subscription Manager instance.
     *
     * @var SubscriptionManager
     */
    protected $subscriptionMgr;

    /**
     * Subscription Service instance.
     *
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * AuthService Service instance.
     *
     * @var AuthService
     */
    protected $authService;

    /**
     * Constructor.
     *
     * @param SubscriptionManager $subscriptionMgr
     * @param SubscriptionService $subscriptionService
     * @param AuthService $authService
     */
    public function __construct(
        SubscriptionManager $subscriptionMgr,
        SubscriptionService $subscriptionService,
        AuthService $authService
    ) {
        $this->middleware('jwt.auth');
        $this->subscriptionMgr = $subscriptionMgr;
        $this->subscriptionService = $subscriptionService;
        $this->authService = $authService;
    }

    /**
     * Pause user subscription.
     *
     * @param integer $id Subscription id.
     *
     * @return Illuminate\Http\Response
     */
    public function pause($id)
    {
        $response = $this->subscriptionMgr->pauseSubscription($id);

        return $this->managerResponse($response);
    }

    /**
     * Resume user subscription.
     *
     * @param integer $id Subscription id.
     *
     * @return Illuminate\Http\Response
     */
    public function resume($id)
    {
        $response = $this->subscriptionMgr->resumeSubscription($id);

        return $this->managerResponse($response);
    }

    /**
     * Cancel user subscription.
     *
     * @param integer $id Subscription id.
     *
     * @return Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $response = $this->subscriptionMgr->cancelSubscription($id);

        return $this->managerResponse($response);
    }

    /**
     * Update user subscription.
     *
     * @param integer $id Subscription id.
     *
     * @return Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $plan = $request->input('planId');
        $response = $this->subscriptionMgr->updateSubscription($id, $plan);

        return $this->managerResponse($response);
    }

    /**
     * Get user past due subscription.
     *
     * @param integer $id Subscription id.
     *
     * @return Illuminate\Http\Response
     */
    public function pastDue($id)
    {
        $apiResponse = $this->subscriptionService->listPastDueUsers($this->authService->getUserId(), $id);

        if (isset($apiResponse)) {
            $data = [
                'updateUrl' => $apiResponse[0]['update_url']
            ];
            return $this->apiResponse(200, $data);
        }

        return $this->apiResponse(
            400,
            [],
            ErrorTypesEnum::RESOURCE_FETCH_ERROR,
            'Something went wrong.'
        );
    }

    /**
     * list payments for the subscription
     * @param integer $id Subscription id
     * @return Illuminate\Http\Response
     */
    public function listUserPayment($id)
    {
        $apiResponse = $this->subscriptionService->listUserPayment(
            $this->authService->getUserId(),
            $id
        );

        if (isset($apiResponse)) {
            $reschedulePaymentId = array_column(
                array_filter($apiResponse, function (array $arr) {
                    return $arr['is_paid'] === 0;
                }),
                'id'
            );

            if (empty($reschedulePaymentId[0])) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypesEnum::LIST_PAYMENT_ID_FAILED,
                    'Something went wrong. Unable to fetch payment id.'
                );
            }

            $rescheduleUserPayment = $this->rescheduleUserPayment($id, $reschedulePaymentId[0]);
            if ($rescheduleUserPayment) {
                return $this->apiResponse(200);
            }
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::SUBSCRIPTION_RESCHEDULE_FAILED,
                'Something went wrong. Unable to reschedule payment.'
            );
        }

        return $this->apiResponse(
            400,
            [],
            ErrorTypesEnum::LIST_PAYMENT_FAILED,
            'Something went wrong. Unable to fetch payment details.'
        );
    }

    /**
     * reschedule payment for subscription
     * @param integer $id Subscription id
     * @param integer $id payment id
     * @return Illuminate\Http\Response
     */
    public function rescheduleUserPayment($id, $paymentId)
    {
        $apiResponse = $this->subscriptionService->rescheduleUserPayment(
            $this->authService->getUserId(),
            $id,
            $paymentId
        );

        if (isset($apiResponse) && $apiResponse['success'] === true) {
            return true;
        }

        return false;
    }

    /**
     * list discount coupon for all plans
     * @return Illuminate\Http\Response
     */
    public function listDiscountCoupon()
    {
        $apiResponse = $this->subscriptionService->getDiscountCoupon();

        if (isset($apiResponse)) {
            $discountCoupon = array_column(
                array_filter($apiResponse, function (array $arr) {
                    return ($arr['allowed_uses'] !== $arr['times_used'] &&
                        $arr['discount_type'] === DiscountTypeEnum::PERCENTAGE &&
                        $arr['discount_amount'] === config('leadgen.paddle_24_hour_discount_amount') &&
                        $arr['expires'] === null);
                }),
                'coupon'
            );

            if (empty($discountCoupon[0])) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypesEnum::RESOURCE_NOT_FOUND,
                    'Unable to find discount coupon.'
                );
            }

            return $this->apiResponse(
                200,
                [
                    'coupon' => $discountCoupon[0]
                ]
            );
        }

        if (empty($discountCoupon[0])) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::RESOURCE_FETCH_ERROR,
                'Something went wrong. Unable to fetch discount coupon.'
            );
        }
    }

    /**
     * list transactions for the subscription - for user
     * @param integer $id Subscription id
     * @return Illuminate\Http\Response
     */
    public function listUserTransaction($id)
    {
        $apiResponse = $this->subscriptionService->listUserTransaction(
            $this->authService->getUserId(),
            $id
        );

        if (isset($apiResponse)) {
            return $this->apiResponse(
                200,
                $apiResponse,
                'Fetched transaction details.'
            );
        } else {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::LIST_TRANSACTION_FAILED,
                'Something went wrong. Unable to fetch transaction details.'
            );
        }
    }
}
