export default {
  INTERNAL_SERVER_ERROR: 'internal_server_error',

  // validation error
  INVALID_DATA: 'invalid_data',

  // resource errors
  RESOURCE_CREATE_ERROR: 'resource_create_error',
  RESOURCE_UPDATE_ERROR: 'resource_update_error',
  RESOURCE_DELETE_ERROR: 'resource_delete_error',
  RESOURCE_FETCH_ERROR: 'resource_fetch_error',
  RESOURCE_COPY_ERROR: 'resource_copy_error',
  RESOURCE_NOT_FOUND: 'No record found',

  // form lead submission error
  RESPONSE_LIMIT_REACHED: 'response_limit_reached',
  ACCEPT_RESPONSES_DISABLED: 'accept_responses_disabled',
  RECAPTCHA_INVALID_RESPONSE: 'recaptcha_invalid_response',
  DOMAIN_NOT_ALLOWED: 'domain_not_allowed',

  // form loading errors
  FORM_GEOLOCATION_FORBIDDEN: 'form_geolocation_forbidden',

  // user login/register/forgot password errors
  INVALID_VERIFICATION_TOKEN: 'invalid_verification_token',
  NON_EXISTENCE_EMAIL: 'non_existence_email',
  INVALID_LOGIN_CREDENTIALS: 'invalid_login_credentials',
  UNVERIFIED_ACCOUNT: 'unverified_account',
  SUSPENDED_ACCOUNT: 'suspended_account',
  ACCOUNT_ALREADY_VERIFIED: 'account_already_verified',
  PASSWORD_RESET_ERROR: 'password_reset_error',
  UNAUTHENTICATED: 'unauthenticated',
  UNAUTHORIZED: 'unauthorized',
  TOKEN_EXPIRED: 'token_expired',
  TOKEN_INVALID: 'token_invalid',

  // experiment
  EXPERIMENT_ALREADY_RUNNING: 'experiment_already_running',
  EXPERIMENT_ALREADY_ENDED: 'experiment_already_ended',
  EXPERIMENT_NOT_STARTED: 'experiment_not_started',
  ANOTHER_EXPERIMENT_RUNNING: 'another_experiment_running',

  // media upload
  INCOMPLETE_UPLOAD: 'incomplete_upload',
  MEDIA_TYPE_NOT_ALLOWED: 'media_type_not_allowed',
  MEDIA_SIZE_EXCEEDED: 'media_size_exceeded',
  MEDIA_TYPE_UNKNOWN: 'media_type_unknown',

  // pages
  PAGE_SLUG_FOUND: 'page_slug_found',

  // google recaptcha
  GOOGLE_RECAPTCHA_KEYS_EXIST: 'google_recaptcha_keys_exist',
  GOOGLE_RECAPTCHA_IN_USE: 'google_recaptcha_in_use',

  // subscription
  FORM_CREATE_EXCEED: 'form_create_exceed',
  ALREADY_SUBSCRIBED_TO_PLAN: 'already_subscribed_to_plan',
  MISSING_STRIPE_CARD_TOKEN: 'missing_stripe_card_token',
  INVALID_STRIPE_COUPON_CODE: 'invalid_stripe_coupon_code'
}
