<?php

namespace App\Enums\Subscription;

use App\Enums\BasicEnum;

abstract class SubscriptionTypesEnum extends BasicEnum
{
    public const PADDLE  = 'paddle';
    public const STRIPE  = 'stripe';
}
