<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Form'               => 'App\Policies\FormPolicy',
        'App\LandingPage'        => 'App\Policies\LandingPagePolicy',
        'App\GoogleRecaptchaKey' => 'App\Policies\GoogleRecaptchaKeyPolicy',
        'App\LeadProof'          => 'App\Policies\LeadProofPolicy',
        'App\Media'              => 'App\Policies\MediaPolicy',
        'App\Models\FormTemplate' => 'App\Policies\FormTemplatePolicy'
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
