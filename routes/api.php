<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
GooglePlaceApiController,
LeadController,
FormPartialLeadController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'api'], function () {
    Route::post('authenticate', 'AuthenticateController@authenticate');
    Route::get('me', 'AuthenticateController@me');
    Route::get('logout', 'AuthenticateController@logout');

    // userLists
    Route::get('users/list', 'UserController@getUserLists');
    Route::post('/send-password-reset-link', 'UserController@sendPasswordResetLink')
        ->name('sendPasswordResetLink');
    Route::post('/reset-password', 'UserController@resetPassword')
        ->name('resetPassword');
    Route::get('/verify-email/{userId}/{token}', 'UserController@verifyEmail')
        ->name('verifyEmail');
    Route::post('/send-email-verification', 'UserController@sendEmailVerification')
        ->name('sendEmailVerification');
    Route::post('/register', 'UserController@register');
    Route::get('/users', 'UserController@index');
    Route::get('/users/{user}', 'UserController@show');
    Route::put('/users/{user}/basicdetails', 'UserController@updateBasicDetails');
    Route::put('/users/{user}/changerole', 'UserController@changeRole');
    Route::put('/users/{user}/activation', 'UserController@activation');
    Route::put('/users/{user}/changepassword', 'UserController@changePassword');
    Route::put('/users/{user}/cancel-subscription', 'PlanController@cancelPlan');

    // admin or user requests for user account to be deleted.
    Route::put('/users/{user}/request-deletion', 'UserController@requestUserDeletion');
    // admin or user cancels delete user request.
    Route::delete('/users/{user}', 'UserController@cancelUserDeletion');

    // user updates credentials - email | password | 2FA
    Route::put('/users/{user}/update-email', 'UserController@updateUserEmail');
    Route::put('/users/{user}/update-password', 'UserController@updateUserPassword');
    Route::put('/users/{user}/update-two-factor', 'UserController@updateTwoFactor');

    // get user profile data
    Route::get('/profile-settings/{user}', 'UserController@getProfileSettings');

    /* return latest signups */
    Route::get('/signups/{count}', 'UserController@signups');
    // formLists
    Route::get('forms/list', 'FormController@getFormLists');
    Route::delete('forms/{forms}/bulkdelete', 'FormController@massDestroy');
    Route::get('forms/{forms}/bulkduplicate', 'FormController@massDuplicate');
    Route::get('/forms/count', 'FormController@count');
    Route::get('/forms/{form}/leads/{variant?}/{experiment?}', 'LeadController@getFormLeads');
    Route::post('/forms/key/{key}', 'FormController@showByKey');
    Route::get('/forms/{id}/setting', 'FormController@setting');
    Route::get('/forms/{id}/integration', 'FormController@integration');
    Route::put('/forms/{id}/setting', 'FormController@saveSetting');
    Route::put('/forms/{id}/footerText', 'FormController@updateFooterText');
    Route::get('/forms/{form}/duplicate', 'FormController@duplicate')->name('forms.duplicate');
    Route::delete('/forms/{form}/archive', 'FormController@archive');
    Route::delete('/forms/{form}/resetstatus', 'FormController@resetFormStatus');
    Route::post('/forms/{form}/share', 'FormController@share');
    Route::get('/forms/{form}/tracking-events', 'Form\FormTrackingEventController@getEvents');
    Route::apiResource('forms', 'FormController');

    Route::post('forms/{key}/visits/bykey', 'FormVisitController@storeByKey');
    Route::put('forms/{key}/visits/{visit_id}/updateinteractiontime', 'FormVisitController@updateInteractionTime');
    Route::apiResource('forms.visits', 'FormVisitController');

    Route::apiResource('forms.experiments.variants', 'FormExperimentVariantController');

    Route::apiResource('form-tracking-events', 'Form\FormTrackingEventController');

    Route::get('/forms/{form}/variants/{variant}/duplicate', 'FormVariantController@duplicate');
    Route::post('/forms/{key}/variants/{variant}/preview', 'FormVariantController@preview');
    Route::get('/forms/{form}/variants/{variant}/promote', 'FormVariantController@promote');
    Route::get('/forms/{form}/variants/{variant}/setting', 'FormVariantController@setting');
    Route::put('/forms/{form}/variants/{variant}/setting', 'FormVariantController@saveSetting');
    Route::delete('/forms/{form}/variants/{variant}/delete', 'FormVariantController@delete');
    Route::apiResource('forms.variants', 'FormVariantController');

    Route::get('forms/{form}/variants/{variant}/theme', 'FormVariantThemeController@show')
        ->name('forms.variants.theme.show');
    Route::post('forms/{form}/variants/{variant}/theme', 'FormVariantThemeController@store')
        ->name('forms.variants.theme.store');
    Route::put('forms/{form}/variants/{variant}/theme', 'FormVariantThemeController@update')
        ->name('forms.variants.theme.update');
    Route::delete('forms/{form}/variants/{variant}/theme', 'FormVariantThemeController@destroy')
        ->name('forms.variants.theme.destroy');

    Route::get('/forms/{form}/experiments/{experiment}/result', 'FormExperimentController@result')
        ->name('forms.experiments.result');
    Route::put('/forms/{form}/experiments/{experiment}/start', 'FormExperimentController@start')
        ->name('forms.experiments.start');
    Route::put('/forms/{form}/experiments/{experiment}/end', 'FormExperimentController@end')
        ->name('forms.experiments.end');
    Route::apiResource('forms.experiments', 'FormExperimentController');

    //partial leads
    Route::post('partial/settings', [FormPartialLeadController::class, 'store']);
    Route::get('partial/{id}/settings', [FormPartialLeadController::class, 'getGlobalPartialSetting']);
    Route::get('partial/{id}/count', [LeadController::class, 'getPartialLeadCounts']);
    Route::get('leads/{id}/count', [LeadController::class, 'getleadsCountPerTimePeriod']);

    Route::post('partial-leads', [LeadController::class, 'storePartialLead']);
    Route::get('leads/average-conversion-rate', 'LeadController@averageConversionRate');
    Route::get('leads/count', 'LeadController@count');
    Route::delete('/forms/{form}/variants/{variant}/bulk-delete', 'LeadController@bulkDelete');
    Route::apiResource('leads', 'LeadController');

    Route::apiResource('visitors', 'VisitorController');

    Route::apiResource('landingpage-templates', 'LandingPageTemplateController');

    Route::put('landingpages/{landingpage}/update-slug', 'LandingPageController@updateSlug')
        ->name('landingpages.updateSlug');
    Route::get('landingpages/{landingpage}/public', 'LandingPageController@showPublic')
        ->name('landingpages.show.public');
    Route::post('landingpages/tpl1', 'LandingPageController@storeTPL1')
        ->name('landingpages.store.TPL1');
    Route::put('landingpages/tpl1/{landingpage}', 'LandingPageController@updateTPL1')
        ->name('landingpages.update.TPL1');
    Route::post('landingpages/all', 'LandingPageController@destroyAll')
        ->name('landingpages.destroy.all');
    Route::resource('landingpages', 'LandingPageController', ['except' => ['edit', 'create', 'store', 'update']]);

    Route::apiResource('landingpage-visits', 'LandingPageVisitController');

    Route::apiResource('landingpage-optins', 'LandingPageOptinController');

    Route::apiResource('googlerecaptchakeys', 'GoogleRecaptchaKeyController', ['parameters' => [
        'googlerecaptchakeys' => 'googleRecaptchaKey'
    ]]);

    // Form contact state routes
    Route::post('form-contactstate', 'FormContactStateController@store');
    Route::put('form-contactstate/{id}', 'FormContactStateController@update');
    Route::get('form-contactstate/{formId}/connectionId/{connectionId}', 'FormContactStateController@show');
    Route::delete('form-contactstate/{id}', 'FormContactStateController@destroy');

    // Form Connections route
    Route::apiResource('formconnections', 'FormConnectionController', ['parameters' => [
        'formconnections' => 'formConnection'
    ]]);

    // Plan Routes
    Route::get('plans', 'PlanController@index')->name('plans.index');
    Route::post('plans/apply', 'PlanController@applyPlan')->name('plans.apply');
    Route::put('plans/change', 'PlanController@changePlan')->name('plans.change');
    Route::put('plans/cancel', 'PlanController@cancelPlan')->name('plans.cancel');
    Route::put('plans/resume', 'PlanController@resumePlan')->name('plans.resume');
    Route::get('plans/{plan}/subscription', 'PlanController@subscription')->name('plans.subscription');

    // Stripe Routes
    Route::post('stripe/setup-intent/{session}', 'StripeController@getSetupIntent')
        ->name('stripe.getSetupIntent');
    Route::post('stripe/coupon-code', 'StripeController@getCouponCode')
        ->name('stripe.couponCode');

    // Dashboard Routes
    Route::post('dashboards/{widget}', 'DashboardController@widget');

    Route::apiResource('forms.webhooks', 'FormWebhookController');

    Route::apiResource('forms.variants.hiddenFields', 'FormHiddenFieldController');

    Route::get('form-theme-templates/default', 'FormThemeTemplateController@getDefaultTemplate');
    Route::put(
        'form-theme-templates/publish/{formThemeTemplate}',
        'FormThemeTemplateController@publishTemplate'
    );
    Route::put(
        'form-theme-templates/deactivate/{formThemeTemplate}',
        'FormThemeTemplateController@deactivateDefaultTemplate'
    );
    Route::apiResource('form-theme-templates', 'FormThemeTemplateController');

    // Lead Proof Routes
    Route::get('lead-proofs/list', 'LeadProofController@getLeadProofLists');
    Route::get('lead-proofs/count', 'LeadProofController@getLeadProofCounts');
    Route::get('lead-proofs/{id}/leads', 'LeadProofController@leads')->name('lead-proofs.leads');
    Route::apiResource('lead-proofs', 'LeadProofController');

    //External Checkout Routes
    //list
    Route::get('external-checkout/list', 'ExternalCheckoutController@getExternalCheckoutLists');
    Route::post('register', 'UserController@register')->name('register');
    Route::post('checkoutlogs', 'ExternalCheckoutController@checkoutlogs')->name('checkoutlogs');
    Route::get('external-checkout/{id}/checkouts', 'ExternalCheckoutController@checkouts')
        ->name('external-checkout.checkouts');
    Route::apiResource('external-checkout', 'ExternalCheckoutController');
    // Media Routes
    Route::get('media/protected/{refId}/{filename}', 'MediaController@protectedMedia')->name('media.protected');

    // WhiteLabel Routes
    Route::get('whitelabels/{id?}', 'WhiteLabelController@showOptional')->name('whitelabels.show.optional');
    Route::put('whitelabels/{whitelabel}', 'WhiteLabelController@update')->name('whitelabels.update');

    // Lead Notification Setting Routes
    Route::get('lead-notifications/{id?}', 'LeadNotificationSettingController@showOptional')
        ->name('lead-notifications.show.optional');
    Route::put('lead-notifications/{leadnotification}', 'LeadNotificationSettingController@update')
        ->name('lead-notifications.update');

    // mark read lead notification
    Route::post('form-lead-view/mark-read', 'FormLeadViewController@markRead');

    //form template browser routes
    Route::get('form-template-browser/templates', 'FormTemplateBrowserController@index');
    Route::post('form-template-browser/{id}/useTemplate', 'FormTemplateBrowserController@useTemplate');
    Route::get('form-template-browser/industries', 'FormTemplateIndustryController@index');
    Route::get('form-template-browser/categories', 'FormTemplateCategoryController@index');

    // Form Template Builder Routes
    //list
    Route::get('form-templates/list', 'FormTemplateController@getFormTemplateLists');
    Route::apiResource('form-templates', 'FormTemplateController');
    Route::apiResource('form-template-categories', 'FormTemplateCategoryController');
    Route::apiResource('form-template-industries', 'FormTemplateIndustryController');

    /* Zapier */
    Route::get('zapier/forms', 'ZapierController@forms');
    Route::get('zapier/form-variants', 'ZapierController@getFormVariants');
    Route::get('zapier/forms/response', 'ZapierController@formResponse');

    // Subscription Routes
    Route::get('subscriptions/{id}/pause', 'SubscriptionController@pause');
    Route::get('subscriptions/{id}/resume', 'SubscriptionController@resume');
    Route::get('subscriptions/{id}/update', 'SubscriptionController@update');
    Route::get('subscriptions/{id}/cancel', 'SubscriptionController@cancel');
    Route::get('subscriptions/{id}/past-due', 'SubscriptionController@pastDue');
    Route::get('subscriptions/{id}/list-payment', 'SubscriptionController@listUserPayment');
    Route::get('subscriptions/list-coupon', 'SubscriptionController@listDiscountCoupon');
    Route::get('subscriptions/{id}/list-transaction', 'SubscriptionController@listUserTransaction');


    // Onboarding Routes
    Route::post('onboarding', 'OnboardingController@save');
    Route::post('onboarding-data', 'OnboardingController@saveData');

    // Icon Routes
    Route::get('icons/{library}/{variation?}', 'IconController@getIcons');

    // Credential Routes
    Route::put('credentials/bulk-update', 'CredentialController@bulkUpdate');
    Route::apiResource('credentials', 'CredentialController');

    // Google Place Api
    Route::get('google/placeapi/{apikey}/search/{input}', [GooglePlaceApiController::class, 'searchPlace']);
    Route::get('google/placeapi/{apikey}/place/{placeId}', [GooglePlaceApiController::class, 'getPlaceDetail']);

    // Black List Ips
    Route::apiResource('blacklist-ips', 'BlacklistIpController');

    /* social login/signup */
    Route::get('{provider}/auth/redirect', 'SocialAuthController@redirectToProvider');
    Route::get('{provider}/auth/callback', 'SocialAuthController@handleCallbackFromProvider');
    Route::post('{provider}/auth/register', 'SocialAuthController@register');
    Route::post('{provider}/auth/login', 'SocialAuthController@login');

    //package builder routes
    Route::get('package-builder/features', 'FeatureController@index');
    Route::get('package-builder/properties', 'FeaturePropertyController@index');
    Route::get('package-builder/list', 'PackageBuilderController@getPackageBuilderList');
    Route::apiResource('package-builder', 'PackageBuilderController');

    //report route
    Route::get('form-report/list', 'ReportController@getReportList');
});

// stripe webhook handler
Route::post(
    'stripe/webhook',
    'StripeWebhookController@handleWebhook'
);

// Media Routes
Route::get('media/public/{filename}', 'MediaController@publicMedia');
Route::get('media/{refId}/{filename}', 'MediaController@media')->name('media.public');
Route::group(['middleware' => 'api'], function () {
    Route::apiResource('media', 'MediaController');
});

/* export leads */
Route::get('forms/{form}/variants/{variant}/leads/export/{format}/partial/leads', 'LeadController@export')
->name('leads.export');
Route::get('forms/{form}/variants/{variant}/leads/export/{format}/partial', 'LeadController@exportPartial');
Route::get('forms/{form}/variants/{variant}/leads/export/{format}/leads', 'LeadController@exportLeads');

/* OneTool */
Route::prefix('onetool')->group(function () {
    Route::post('user/create', 'OneToolController@createUser');
    Route::get('user/login', 'OneToolController@loginUser');
    Route::get('user/get', 'OneToolController@getUser');
    Route::post('user/update', 'OneToolController@updateUser');
    Route::delete('user/delete', 'OneToolController@deleteUser');
});

/* email verification settings routes */
Route::get('user-settings', 'UserSettingsController@getUserSettings');
Route::put('user-settings/{emailverification}', 'UserSettingsController@update');
