<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Stripe\StripeManager;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use Log;

class StripeController extends Controller
{
    /**
     * Stripe Manager instance.
     *
     * @var StripeManager
     */
    protected $stripeMgr;

    /**
     * Constructor.
     *
     * @param StripeManager $stripeMgr
     */
    public function __construct(StripeManager $stripeMgr)
    {
        $this->stripeMgr = $stripeMgr;
    }

    /**
     * Get Stripe Setup Intent with Meta Data.
     *
     * @param Request $request Http Request.
     * @param string $session On or Off session.
     * @return void
     */
    public function getSetupIntent(Request $request, $session = 'on_session')
    {
        $response = $this->stripeMgr->getSetupIntent($request->all(), $session);

        return $this->managerResponse($response);
    }

    /**
     * Get Stripe Coupon Object.
     *
     * @param Request $request
     * @return void
     */
    public function getCouponCode(Request $request)
    {
        if (!$request->has('coupon')) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'coupon field is missing.'
            );
        }

        $coupon = $request->input('coupon');

        $response = $this->stripeMgr->getCouponCode($coupon);

        return $this->managerResponse($response);
    }
}
