<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class EnvironmentsEnum extends BasicEnum
{
    public const STAGING = 'staging';
    public const PRODUCTION = 'production';
    public const LOCAL = 'local';
}
