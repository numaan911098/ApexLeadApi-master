<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorTypes;

class Subscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if (!$user->active) {
            return Util::apiResponse(
                400,
                [],
                ErrorTypes::SUSPENDED_ACCOUNT,
                'Your account has been suspended, please contact our support team.'
            );
        }

        if ($user->isAdmin() || $user->isSuperCustomer() || $user->isOneToolUser()) {
            return $next($request);
        }

        $plan      = $user->plan();
        $routeName = $request->route()->getName();
        $routes    = [
            'forms.store',
            'forms.duplicate',
        ];

        if (!in_array($routeName, $routes, true)) {
            return $next($request);
        }

        if (
            $user->forms->count() < $plan->form_limit &&
            ($plan->isFreePlan() || $plan->isFreeTrialPlan() || $user->hasActiveSubscription($plan))
        ) {
            return $next($request);
        }
        
        return Util::apiResponse(
            403,
            [],
            ErrorTypes::FORM_CREATE_EXCEED,
            'You have exceeded the limit for creating the forms, please upgrade your plan.'
        );
    }
}
