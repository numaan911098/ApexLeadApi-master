<?php

namespace App\Enums;

abstract class ConfigKeyEnum extends BasicEnum
{
    // APP
    public const APP_ENV = 'app.env';

    public const ENABLE_SLACK_REPORT = 'leadgen.slack.enable_report';
    public const LEADGEN_SLACK_REPORT_CHANNEL = 'leadgen.slack.report_channel';
    public const LEADGEN_SLACK_PHISHING_FORM_REPORT_CHANNEL = 'leadgen.slack.phishing_form_report_channel';
    public const LEADGEN_SLACK_USER_DELETION_CHANNEL = 'leadgen.slack.user_deletion_channel';

    // REGISTRATION
    public const LEADGEN_USER_ACCOUNTS_PER_IP = 'leadgen.registration.user_accounts_per_ip';
    public const LEADGEN_REGISTRATION_RECAPTCHA_ENABLED = 'leadgen.registration.recaptcha_enabled';
}
