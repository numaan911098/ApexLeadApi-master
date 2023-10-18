<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class FormConnectionsEnum extends BasicEnum
{
    public const WEBHOOK = 'webhook';
    public const CONTACTSTATE = 'contactstate';
    public const TRUSTEDFORM = 'trustedform';
}
