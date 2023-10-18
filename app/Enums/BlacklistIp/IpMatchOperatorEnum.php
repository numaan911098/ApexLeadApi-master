<?php

namespace App\Enums\BlacklistIp;

use App\Enums\BasicEnum;

abstract class IpMatchOperatorEnum extends BasicEnum
{
    public const CONTAINS = 'CONTAINS';
    public const EQUAL = 'EQUAL';
}
