<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Validator;
use Log;
use Facades\App\Services\Util;
use App\FormVariant;
use App\Plan;
use App\PaddleSubscription;
use App\Observers\FormVariantObserver;
use App\Observers\PlanObserver;
use App\Observers\PaddleSubscriptionObserver;
use Illuminate\Pagination\Paginator;
use Laravel\Cashier\Cashier;
use Auth;
use \App\Modules\Subscription\Handlers\PaddleSubscriptionHandler;
use App\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Paginator::useBootstrap();

        // Model observers.
        Plan::observe(PlanObserver::class);
        PaddleSubscription::observe(PaddleSubscriptionObserver::class);

        // Extend validator.
        Validator::extend('comma_separated_emails', function ($attribute, $value, $parameters, $validator) {
            $value = str_replace(' ', '', $value);
            $array = explode(',', $value);
            foreach ($array as $email) {
                $email_to_validate['alert_email'][] = $email;
            }
            $rules = array('alert_email.*' => 'email');
            $messages = array(
                '' . $attribute => trans('Please enter comma separated emails.')
            );
            $validator = Validator::make($email_to_validate, $rules, $messages);
            if ($validator->passes()) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('domain_array', function ($attribute, $value, $parameters, $validator) {
            $value = str_replace(' ', '', $value);
            $array = explode(',', $value);
            $valid = true;
            foreach ($array as $domain) {
                $valid = $valid && Util::isValidDomainName($domain);
            }
            return $valid;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Laravel cashier.
         */
        Cashier::ignoreMigrations();

        /**
         * Subscription Services.
         */
        $this->app->bind('App\Modules\Subscription\Contracts\SubscriptionHandlerInterface', function ($app, $args) {
            $user = Auth::user();

            if (!$user && isset($args['user_id'])) {
                $user = User::find($args['user_id']);
            }

            if (empty($user)) {
                return null;
            }

            $subscription = $user->getSubscription();

            if (empty($subscription)) {
                return null;
            }

            if ($subscription instanceof \App\PaddleSubscription) {
                return new PaddleSubscriptionHandler();
            }

            return null;
        });
    }
}
