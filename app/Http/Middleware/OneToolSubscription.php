<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\StripePlansEnum;

class OneToolSubscription
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

        if ($user->isAdmin() || $user->isSuperCustomer()) {
            return $next($request);
        }

        $routeName = $request->route()->getName();

        if ($routeName !== 'forms.store' && $routeName !== 'forms.duplicate') {
            return $next($request);
        }

        if (!$user->isOneToolUser()) {
            return $next($request);
        }

        if (!$user->hasActiveOneToolSubscription()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::FORM_CREATE_EXCEED,
                'You have exceeded the limit for creating the forms, please upgrade your plan.'
            );
        }

        if (
            (!$user->hasOneToolProPlan() && $user->forms->count() >= 3) ||
            ($user->hasOneToolProPlan() && $user->forms->count() >= 100)
        ) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::FORM_CREATE_EXCEED,
                'You have exceeded the limit for creating the forms, please upgrade your plan.'
            );
        }

        return $next($request);
    }
}
