<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API, CLIENT, FORMS, PAGES, PROOFS URL
    |--------------------------------------------------------------------------
    |
    */
    'app_url'                                   => env('APP_URL', ''),
    'api_url'                                   => env('API_URL', ''),
    'app_env'                                   => env('APP_ENV', ''),
    'client_app_url'                            => env('CLIENT_APP_URL', ''),
    'client_app_token_login_url'                => env('CLIENT_APP_TOKEN_LOGIN_URL', ''),
    'pages_domain'                              => env('PAGES_DOMAIN', ''),
    'forms_domain'                              => env('FORMS_DOMAIN', ''),
    'proofs_domain'                             => env('PROOFS_DOMAIN', ''),

    'registration'                             => [
        'user_accounts_per_ip' => env('USER_ACCOUNTS_PER_IP', 5),
        'recaptcha_enabled'    => env( 'LEADGEN_REGISTRATION_RECAPTCHA_ENABLED', true ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Invisible Recaptcha Credentials app.leadgenapp.io
    |--------------------------------------------------------------------------
    |
    */
    'google_irecaptcha_site_key'                => env('GOOGLE_IRECAPTCHA_SITEKEY', ''),
    'google_irecaptcha_secret_key'              => env('GOOGLE_IRECAPTCHA_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Invisible Recaptcha Credentials pages.leadgenapp.io
    |--------------------------------------------------------------------------
    |
    */
    'google_irecaptcha_site_key_pages_domain'   => env('GOOGLE_IRECAPTCHA_SITEKEY_PAGES_DOMAIN', ''),
    'google_irecaptcha_secret_key_pages_domain' => env('GOOGLE_IRECAPTCHA_SECRET_PAGES_DOMAIN', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Invisible Recaptcha Credentials forms.leadgenapp.io
    |--------------------------------------------------------------------------
    |
    */
    'google_irecaptcha_site_key_forms_domain'   => env('GOOGLE_IRECAPTCHA_SITEKEY_FORMS_DOMAIN', ''),
    'google_irecaptcha_secret_key_forms_domain' => env('GOOGLE_IRECAPTCHA_SECRET_FORMS_DOMAIN', ''),

    /*
    |--------------------------------------------------------------------------
    | ConvertKit Credentials for newsletter or email marketting.
    |--------------------------------------------------------------------------
    |
    */
    'convert_kit_enabled'                       => env('CONVERT_KIT_ENABLED', false),
    'convert_kit_api_key'                       => env('CONVERT_KIT_API_KEY', ''),
    'convert_kit_api_secret'                    => env('CONVERT_KIT_API_SECRET', ''),
    'convert_kit_sequence_id'                   => env('CONVERT_KIT_SEQUENCE_ID', ''),
    'convert_kit_free_tag_id'                   => env('CONVERT_KIT_FREE_TAG_ID', ''),
    'convert_kit_pro_tag_id'                    => env('CONVERT_KIT_PRO_TAG_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Active Campaign
    |--------------------------------------------------------------------------
    |
    */
    'active_campaign_enabled'                       => env('ACTIVE_CAMPAIGN_ENABLED', true),
    'active_campaign_api_token'                     => env('ACTIVE_CAMPAIGN_API_TOKEN', ''),
    'active_campaign_free_tag_id'                   => env('ACTIVE_CAMPAIGN_FREE_TAG_ID', ''),
    'active_campaign_pro_tag_id'                    => env('ACTIVE_CAMPAIGN_PRO_TAG_ID', ''),
    'active_campaign_pro_paused_tag_id'             => env('ACTIVE_CAMPAIGN_PRO_PAUSED_TAG_ID', ''),
    'active_campaign_previous_customer_tag_id'      => env('ACTIVE_CAMPAIGN_PREVIOUS_CUSTOMER_TAG_ID', ''),
    'active_campaign_onetool_pro_lite_trial_tag_id' => env('ACTIVE_CAMPAIGN_ONETOOL_PRO_LITE_TRIAL_TAG_ID', ''),
    'active_campaign_onetool_pro_lite_tag_id'       => env('ACTIVE_CAMPAIGN_ONETOOL_PRO_LITE_TAG_ID', ''),
    'active_campaign_pro_trial_tag_id'              => env('ACTIVE_CAMPAIGN_PRO_TRIAL_TAG_ID', ''),
    'active_campaign_pro_trial_cancelled_tag_id'    => env('ACTIVE_CAMPAIGN_PRO_TRIAL_CANCELLED_TAG_ID', ''),
    'active_campaign_scale_tag_id'                  => env('ACTIVE_CAMPAIGN_SCALE_TAG_ID', ''),
    'active_campaign_scale_paused_tag_id'           => env('ACTIVE_CAMPAIGN_SCALE_PAUSED_TAG_ID', ''),
    'active_campaign_plan_cancelled'                => env('ACTIVE_CAMPAIGN_PLAN_CANCELLED_TAG_ID', ''),
    'active_campaign_plan_trial_end'                => env('ACTIVE_CAMPAIGN_PLAN_TRIAL_END_TAG_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    |
    */
    'stripe_test_pro_plan_id'                   => env('STRIPE_TEST_PRO_PLAN_ID', ''),
    'stripe_live_pro_plan_id'                   => env('STRIPE_LIVE_PRO_PLAN_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | OneTool
    |--------------------------------------------------------------------------
    |
    */
    'onetool_secret'                            => env('ONETOOL_SECRET', ''),
    'onetool_basic_lite_plan_id'                => env('ONETOOL_BASIC_LITE_PLAN_ID', ''),
    'onetool_pro_lite_plan_id'                  => env('ONETOOL_PRO_LITE_PLAN_ID', ''),
    'onetool_pro_plan_id'                       => env('ONETOOL_PRO_PLAN_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Paddle
    |--------------------------------------------------------------------------
    |
    */
    'paddle_vendor_id'                          => env('PADDLE_VENDOR_ID', ''),
    'paddle_vendor_auth_code'                   => env('PADDLE_VENDOR_AUTH_CODE', ''),
    'paddle_public_key'                         => env('PADDLE_PUBLIC_KEY', ''),
    'paddle_pro_plan_id'                        => env('PADDLE_PRO_PLAN_ID', ''),
    'paddle_pro_annual_plan_id'                 => env('PADDLE_PRO_ANNUAL_PLAN_ID', ''),
    'paddle_pro2_plan_id'                       => env('PADDLE_PRO2_PLAN_ID', ''),
    'paddle_pro_trial_plan_id'                  => env('PADDLE_PRO_TRIAL_PLAN_ID', ''),
    'paddle_scale_plan_id'                      => env('PADDLE_SCALE_PLAN_ID', ''),
    'paddle_scale_annual_plan_id'               => env('PADDLE_SCALE_ANNUAL_PLAN_ID', ''),
    'paddle_scale_trial_plan_id'                => env('PADDLE_SCALE_TRIAL_PLAN_ID', ''),
    'paddle_scale_annual_trial_plan_id'         => env('PADDLE_SCALE_ANNUAL_TRIAL_PLAN_ID', ''),
    'paddle_pro_annual_trial_plan_id'           => env('PADDLE_PRO_ANNUAL_TRIAL_PLAN_ID', ''),
    'paddle_enterprise_plan_id'                 => env('PADDLE_ENTERPRISE_PLAN_ID', ''),
    'paddle_enterprise_annual_plan_id'          => env('PADDLE_ENTERPRISE_ANNUAL_PLAN_ID', ''),
    'paddle_enterprise_trial_plan_id'           => env('PADDLE_ENTERPRISE_TRIAL_PLAN_ID', ''),
    'paddle_enterprise_annual_trial_plan_id'    => env('PADDLE_ENTERPRISE_ANNUAL_TRIAL_PLAN_ID', ''),
    'paddle_single_annual_plan_id'              => env('PADDLE_SINGLE_ANNUAL_PLAN_ID', ''),
    'paddle_24_hour_discount_amount'            => env('PADDLE_24_HOUR_DISCOUNT_AMOUNT', ''),

    /*
    | Onboarding
    |--------------------------------------------------------------------------
    |
    */
    'onboarding'                                => env('ONBOARDING', ''),

    /*
    | Branding
    |--------------------------------------------------------------------------
    |
    */
    'branding' => [
        'url'   => env('LEADGEN_BRANDING_URL', 'https://leadgenapp.io/'),
        'title' => env('LEADGEN_BRANDING_TITLE', 'LeadGen App'),
        'prefix' => env('LEADGEN_BRANDING_PREFIX', 'Built with'),
    ],

    /*
    |
    |--------------------------------------------------------------------------
    |
    */
    'emails' => [
        'leadgen' => [
            'hello' => [
                'email' => 'hello@leadgenapp.io',
            ],
        ],
    ],

    'slack' => [
        'enable_report' => env('ENABLE_SLACK_REPORT'),
        'report_channel' => env('LEADGEN_SLACK_REPORT_CHANNEL'),
        'phishing_form_report_channel' => env('LEADGEN_SLACK_PHISHING_FORM_REPORT_CHANNEL'),
        'user_deletion_channel' => env('LEADGEN_SLACK_USER_DELETION_CHANNEL'),

    ],

    /*
    | GDPR
    |--------------------------------------------------------------------------
    |
    */
    'gdpr' => [
        'user_inactive_period' => env('USER_INACTIVE_PERIOD', '')
    ],

    /*
    | Past due user second email
    |--------------------------------------------------------------------------
    |
    */
    'past_due_user' => [
        'email' => env('SEND_USER_PAST_DUE_MAIL', '')
    ],
];
