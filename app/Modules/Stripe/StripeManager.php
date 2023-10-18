<?php

namespace App\Modules\Stripe;

use App\Modules\Base\BaseManager;
use App\Enums\ErrorTypesEnum;
use Stripe\Stripe;
use Auth;
use Log;

class StripeManager extends BaseManager
{
    /**
     * Stripe Instance.
     *
     * @var Stripe
     */
    private $stripe;
    public function __construct()
    {
        Stripe::setApiKey(config('cashier.secret'));
    }

    /**
     * Get Setup Intent.
     *
     * @param string $session Session Type.
     *
     * @return void
     */
    public function getSetupIntent(array $meta, $session)
    {
        $user            = Auth::user();
        $meta['user_id'] = $user->id;
        $intent = $user->createSetupIntent([
            'usage' => $session,
            'metadata' => $meta,
        ]);
        return $this->fillResponse([
            'data' => [
                'client_secret' => $intent->client_secret,
            ]
        ])->response();
    }

    public function getCouponCode($coupon)
    {
        try {
            $coupon = \Stripe\Coupon::retrieve($coupon);
            $this->addResponse('data', $coupon);
            return $this->response();
        } catch (\Exception $e) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::INVALID_DATA,
                'error_message' => $e->getMessage(),
            ])->response();
        }
    }
}
