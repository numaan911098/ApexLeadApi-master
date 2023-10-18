<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class DiscountTypeEnum extends BasicEnum
{
    public const PERCENTAGE = 'percentage';
    public const FLAT = 'flat';
}
