<?php

namespace App\Providers;

use App\Events\FormVariantCreated;
use App\Listeners\Form\DetectPhishingContent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        'App\Events\LeadCreated' => [
            'App\Listeners\LeadCreatedListener',
            'App\Listeners\EmailNotificationListener',
            'App\Listeners\SendWebhookListener',
            'App\Listeners\ContactStateListener',
        ],

        'App\Events\UserRegistered' => [
            'App\Listeners\SendVerificationEmailListener',
            'App\Listeners\ActiveCampaign\AddContact',
            'App\Listeners\CaptureUserGeolocation',
        ],

        'App\Events\SocialUserRegistered' => [
            'App\Listeners\ActiveCampaign\AddContact',
            'App\Listeners\CaptureUserGeolocation',
        ],

        'App\Events\OneToolUserRegistered' => [
            'App\Listeners\ActiveCampaign\AddContact',
            'App\Listeners\CaptureUserGeolocation',
        ],

        'App\Events\FormCreated' => [
            'App\Listeners\Form\DetectPhishingContent',
        ],

        FormVariantCreated::class => [
            DetectPhishingContent::class
        ],

        'App\Events\FormVariantUpdated' => [
            'App\Listeners\UpdateWebhookFieldsMapListener',
            DetectPhishingContent::class
        ],

        // 'App\Events\ProUserRegistered' => [
        //     'App\Listeners\ActiveCampaign\UpdateContactWithProTag',
        // ],

        // 'App\Events\ProUserCancelled' => [
        //     'App\Listeners\ActiveCampaign\UpdateContactWithProPausedTag',
        // ],

        // 'App\Events\ProTrialUserRegistered' => [
        //     'App\Listeners\ActiveCampaign\UpdateContactWithProTrialTag',
        // ],

        // 'App\Events\ProTrialUserCancelled' => [
        //     'App\Listeners\ActiveCampaign\UpdateContactWithProTrialCancelledTag',
        // ],

        // 'App\Events\ScaleUserRegistered' => [
        //     'App\Listeners\ActiveCampaign\UpdateContactWithScaleTag',
        // ],

        // 'App\Events\ScaleUserCancelled' => [
        //     'App\Listeners\ActiveCampaign\UpdateContactWithScalePausedTag',
        // ],

        'App\Events\UserLoggedIn' => [
            'App\Listeners\CaptureUserGeolocation',
            // 'App\Listeners\ActiveCampaign\UpdateContactWithPreviousCustomerTag',
            'App\Listeners\LogSuccessfulLogin',
            'App\Listeners\RemoveFromInactiveAccounts',
        ],

        'ProtoneMedia\LaravelPaddle\Events\SubscriptionCreated' => [
            'App\Listeners\Paddle\SubscriptionCreated',
        ],
        'ProtoneMedia\LaravelPaddle\Events\SubscriptionUpdated' => [
            'App\Listeners\Paddle\SubscriptionUpdated',
        ],
        'ProtoneMedia\LaravelPaddle\Events\SubscriptionCancelled' => [
            'App\Listeners\Paddle\SubscriptionCancelled',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }
}
