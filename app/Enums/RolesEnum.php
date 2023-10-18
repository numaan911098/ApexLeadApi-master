<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class RolesEnum extends BasicEnum
{
    public const ADMIN = 'ADMIN';
    public const CUSTOMER = 'CUSTOMER';
    public const SUPER_CUSTOMER = 'SUPER_CUSTOMER';
}
