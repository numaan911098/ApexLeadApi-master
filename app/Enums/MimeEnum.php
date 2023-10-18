<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class MimeEnum extends BasicEnum
{
    public const JSON = "application/json";
    public const FORM_URLENCODED = "application/x-www-form-urlencoded";
}
