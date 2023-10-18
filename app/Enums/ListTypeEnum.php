<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class ListTypeEnum extends BasicEnum
{
    public const FORM = 'form';
    public const USER = 'user';
    public const LEAD_PROOF = 'lead_proof';
    public const EXTERNAL_CHECKOUT = 'external_checkout';
    public const FORM_TEMPLATE = 'form_template';
    public const PACKAGE_BUILDER = 'package_builder';
    public const USER_FORM_REPORT = 'user_form_report';
}
