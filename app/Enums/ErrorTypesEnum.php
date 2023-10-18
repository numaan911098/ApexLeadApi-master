<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class ErrorTypesEnum extends BasicEnum
{
    public const INTERNAL_SERVER_ERROR = "internal_server_error";

    // validation error
    public const INVALID_DATA = "invalid_data";
    public const BLACKLISTED_IP = 'blacklisted_ip';

    // resource errors
    public const RESOURCE_CREATE_ERROR = 'resource_create_error';
    public const RESOURCE_UPDATE_ERROR = 'resource_update_error';
    public const RESOURCE_DELETE_ERROR = 'resource_delete_error';
    public const RESOURCE_FETCH_ERROR = 'resource_fetch_error';
    public const RESOURCE_COPY_ERROR = 'resource_copy_error';
    public const RESOURCE_NOT_FOUND = "No record found";

    // form lead submission error
    public const RESPONSE_LIMIT_REACHED = 'response_limit_reached';
    public const ACCEPT_RESPONSES_DISABLED = 'accept_responses_disabled';
    public const RECAPTCHA_INVALID_RESPONSE = 'recaptcha_invalid_response';
    public const DOMAIN_NOT_ALLOWED = 'domain_not_allowed';
    public const LEADS_LIMIT_REACHED = 'leads_limit_reached';
    public const LEAD_SAVE_ERROR = 'lead_save_error';

    // form loading errors
    public const FORM_GEOLOCATION_FORBIDDEN = 'form_geolocation_forbidden';

    // user login/register/forgot password errors
    public const INVALID_VERIFICATION_TOKEN = 'invalid_verification_token';
    public const NON_EXISTENCE_EMAIL = 'non_existence_email';
    public const INVALID_LOGIN_CREDENTIALS = 'invalid_login_credentials';
    public const UNVERIFIED_ACCOUNT = 'unverified_account';
    public const SUSPENDED_ACCOUNT = 'suspended_account';
    public const ACCOUNT_GOOGLE_VERIFIED = 'account_google_verified';
    public const ACCOUNT_ALREADY_VERIFIED = 'account_already_verified';
    public const PASSWORD_RESET_ERROR = 'password_reset_error';
    public const UNAUTHENTICATED = "unauthenticated";
    public const UNAUTHORIZED = "unauthorized";
    public const TOKEN_EXPIRED = 'token_expired';
    public const TOKEN_INVALID = 'token_invalid';
    public const EMAIL_ALREADY_EXIST = 'email_already_exist';
    public const TOO_MANY_ATTEMPTS = 'too_many_attempts';
    public const GENERIC_ERROR = 'generic_error';
    public const ONETOOL_USER_LOGIN = 'onetool_user_login';
    public const SIGNUP_INCOMPLETE = 'signup_incomplete';
    public const INCOMPLETE_TWO_FACTOR = 'incomplete_two_factor';
    public const INVALID_TWO_FACTOR_CODE = 'invalid_two_factor_code';
    public const TOO_MANY_ACCOUNTS = 'too_many_accounts';

    // experiment
    public const EXPERIMENT_ALREADY_RUNNING = 'experiment_already_running';
    public const EXPERIMENT_ALREADY_ENDED = 'experiment_already_ended';
    public const EXPERIMENT_NOT_STARTED = 'experiment_not_started';
    public const ANOTHER_EXPERIMENT_RUNNING = 'another_experiment_running';

    // media upload
    public const INCOMPLETE_UPLOAD = 'incomplete_upload';
    public const MEDIA_TYPE_NOT_ALLOWED = 'media_type_not_allowed';
    public const MEDIA_SIZE_EXCEEDED = 'media_size_exceeded';
    public const MEDIA_TYPE_UNKNOWN = 'media_type_unknown';

    // pages
    public const PAGE_SLUG_FOUND = 'page_slug_found';

    // google recaptcha
    public const GOOGLE_RECAPTCHA_KEYS_EXIST = 'google_recaptcha_keys_exist';
    public const GOOGLE_RECAPTCHA_IN_USE = 'google_recaptcha_in_use';

    // subscription
    public const FORM_CREATE_EXCEED = 'form_create_exceed';
    public const LANDINGPAGE_CREATE_EXCEED = 'landingpage_create_exceed';
    public const ALREADY_SUBSCRIBED_TO_PLAN = 'already_subscribed_to_plan';
    public const MISSING_STRIPE_CARD_TOKEN = 'missing_stripe_card_token';
    public const INVALID_STRIPE_COUPON_CODE = 'invalid_stripe_coupon_code';
    public const SUBSCRIPTION_PAUSE_FAILED = 'subscription_pause_failed';
    public const SUBSCRIPTION_CANCEL_FAILED = 'subscription_cancel_failed';
    public const SUBSCRIPTION_RESUME_FAILED = 'subscription_resume_failed';
    public const SUBSCRIPTION_UPDATE_FAILED = 'subscription_update_failed';
    public const SUBSCRIPTION_ALREADY_PAUSED = 'subscription_already_paused';
    public const SUBSCRIPTION_ALREADY_RESUMED = 'subscription_already_resumed';
    public const SUBSCRIPTION_ALREADY_CANCELLED = 'subscription_already_cancelled';
    public const LIST_PAYMENT_FAILED = 'list_payment_failed';
    public const LIST_PAYMENT_ID_FAILED = 'list_payment_id_failed';
    public const SUBSCRIPTION_RESCHEDULE_FAILED = 'subscription_reschedule_failed';
    public const LIST_TRANSACTION_FAILED = 'list_transaction_failed';
    public const LIST_TRANSACTION_ID_FAILED = 'list_transaction_id_failed';

    // export
    public const EXPORT_FORMAT_INVALID = 'export_format_invalid';

    // dashboard
    public const INVALID_DASHBOARD_WIDGET_TYPE = 'invalid_dashboard_widget_type';

    // from template builder
    public const TEMPLATE_ID_ALREADY_EXIST = 'template_id_already_exist';

    // contact state
    public const CONTACT_STATE_EXISTS =  'contact_state_exists';

    // Recaptcha
    public const INVALID_GRECAPTCHA_TOKEN = 'invalid_grecaptcha_token';

    // Package Builer
    public const NO_FEATURE_ACCESS = 'no_feature_access';
    public const INAPPROPRIATE_PLAN = 'inappropriate_plan';

    // Trusted Form
    public const TRUSTEDFORM_SCRIPT = 'trustedform_script';
}
