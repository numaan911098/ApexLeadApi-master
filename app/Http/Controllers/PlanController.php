<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Enums\SubscriptionsEnum;
use App\Enums\StripePlansEnum;
use App\Enums\PlansEnum;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use Facades\App\Services\Util;
use App\Events\ProUserRegistered;
use App\User;
use App\Plan;
use Auth;
use Log;
use Validator;
use DB;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function index()
    {
        return $this->apiResponse(200, Plan::where('external_checkout_enabled', 1)->get()->toArray());
    }

    public function subscription(Plan $plan)
    {
        $user = Auth::user();

        if (empty($user->stripe_id) || empty($plan->stripe_plan_id)) {
            return $this->apiResponse(
                404,
                [],
                ErrorTypes::RESOURCE_NOT_FOUND,
                'No subscription details were found.'
            );
        }

        $subscriptions = $user->asStripeCustomer()["subscriptions"]['data'];

        foreach ($subscriptions as $subscription) {
            if (
                !empty($subscription['plan']) &&
                $subscription['plan']['id'] === $plan->stripe_plan_id
            ) {
                return $this->apiResponse(200, [
                    'current_period_start' => $subscription['current_period_start'],
                    'current_period_end' => $subscription['current_period_end'],
                    'cancel_at_period_end' => $subscription['cancel_at_period_end']
                ]);
            }
        }

        return $this->apiResponse(
            404,
            [],
            ErrorTypes::RESOURCE_NOT_FOUND,
            'No subscription details were found.'
        );
    }

    public function applyPlan(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'plan_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $validPlan = false;
        $newPlan = null;
        foreach (StripePlansEnum::getConstants() as $plan) {
            if ($plan['public_id'] === $request->input('plan_id')) {
                $validPlan = true;
                $newPlan = $plan;
                break;
            }
        }
        if (!$validPlan) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_PLAN,
                'plan_id is incorrect'
            );
        }

        $user = Auth::user();

        if ($user->subscribed(SubscriptionsEnum::MAIN)) {
            if ($user->subscribedToPlan($newPlan['id'], SubscriptionsEnum::MAIN)) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::ALREADY_SUBSCRIBED_TO_PLAN,
                    'You are already subscribed to plan ' . $newPlan['name']
                );
            }
            $user->subscription(SubscriptionsEnum::MAIN)
                ->swap($newPlan['id']);
        } else {
            // needs stripe token
            if (empty($request->input('stripe_card_token'))) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::INVALID_DATA,
                    'stripe_card_token is required for users without any plan.'
                );
            } else {
                $user->newSubscription(SubscriptionsEnum::MAIN, $newPlan['id'])
                    ->create($request->input('stripe_card_token'));
            }
        }

        return $this->apiResponse(200);
    }

    public function changePlan(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'plan_id' => [
                'required',
                Rule::in(StripePlansEnum::getConstants())
            ]
        ]);

        $user = Auth::user();
        $userPlan = $user->plan();

        if ($request->input('plan_id') === $userPlan->public_id) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::ALREADY_SUBSCRIBED_TO_PLAN,
                'You are already using this plan'
            );
        }

        if ($user->subscribed(SubscriptionsEnum::MAIN)) {
            // already subscribed
            if ($request->input('plan_id') === PlansEnum::FREE) {
                try {
                    DB::beginTransaction();

                    $user->subscription(SubscriptionsEnum::MAIN)->cancel();

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();

                    Util::logException($e);
                }
            } else {
                if (!$this->swapPlan($request, $user)) {
                    return $this->apiResponse(
                        400,
                        [],
                        ErrorTypes::ALREADY_SUBSCRIBED_TO_PLAN,
                        'You are already using this plan'
                    );
                }
            }
        } elseif ($request->input('plan_id') !== PlansEnum::FREE) {
            // new subscriber
            $stripeCardToken = $request->input('stripe_card_token');

            if (empty($stripeCardToken)) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::MISSING_STRIPE_CARD_TOKEN,
                    'stripe_card_token is required'
                );
            }

            $plan = Plan::where('public_id', $request->input('plan_id'))
                    ->firstOrFail();

            try {
                DB::beginTransaction();

                $subscription = $user->newSubscription(
                    SubscriptionsEnum::MAIN,
                    $plan->stripe_plan_id
                );
                if ($request->filled('stripe_coupon_code')) {
                    $subscription->withCoupon($request->input('stripe_coupon_code'));
                }
                $subscription->create($request->input('stripe_card_token'));

                DB::commit();

                $user = User::find($user->id);
                $user->agree_terms = $data['agree_terms'];
                $user->subscribe_newsletter = $data['subscribe_newsletter'];
                $user->save();

                event(new ProUserRegistered($user));
            } catch (\Exception $e) {
                DB::rollback();

                Util::logException($e);

                if (Str::contains($e->getMessage(), 'coupon')) {
                    return $this->apiResponse(
                        400,
                        [],
                        ErrorTypes::INVALID_STRIPE_COUPON_CODE,
                        'Invalid coupon sent'
                    );
                }

                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::RESOURCE_UPDATE_ERROR,
                    'Unable to subscribe'
                );
            }
        }

        return $this->apiResponse(200, $user->plan()->toArray());
    }

    public function cancelPlan(User $user = null)
    {
        if (empty($user)) {
            $user = Auth::user();
        }

        $plan = $user->plan();

        if ($user->subscription(SubscriptionsEnum::MAIN)->onGracePeriod()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'Your plan is on grace period'
            );
        }

        if (
            $plan->public_id !== PlansEnum::FREE &&
            $user->subscribedToPlan($plan->stripe_plan_id, SubscriptionsEnum::MAIN)
        ) {
            try {
                DB::beginTransaction();

                $user->subscription(SubscriptionsEnum::MAIN)->cancel();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();

                Util::logException($e);
            }
            return $this->apiResponse(200);
        } else {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'You are not subscribed to any plan yet'
            );
        }
    }

    public function resumePlan()
    {
        $user = Auth::user();
        $plan = $user->plan();

        if (
            $plan->public_id !== PlansEnum::FREE &&
            $user->subscribedToPlan($plan->stripe_plan_id, SubscriptionsEnum::MAIN) &&
            $user->subscription(SubscriptionsEnum::MAIN)->onGracePeriod()
        ) {
            try {
                DB::beginTransaction();

                $user->subscription(SubscriptionsEnum::MAIN)->resume();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();

                Util::logException($e);
            }
            return $this->apiResponse(200);
        } else {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_UPDATE_ERROR,
                'You are not subscribed to any plan yet'
            );
        }
    }

    protected function swapPlan(Request $request, User $user)
    {
        $userPlan = $user->plan();
        if ($userPlan->public_id === $request->input('plan_id')) {
            return false;
        }
        $newPlan = Plan::where('public_id', $request->input('plan_id'))->firstOrFail();
        if (!empty($newPlan->stripe_plan_id)) {
            $user->subscription(SubscriptionsEnum::MAIN)
                ->swap($newPlan->stripe_plan_id);
        } else {
            $user->subscription(SubscriptionsEnum::MAIN)->cancelNow();
        }
        return true;
    }
}
