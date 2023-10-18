<?php

namespace App\Enums\Paddle;

use App\Enums\BasicEnum;

abstract class PaddleAlertTypesEnum extends BasicEnum
{
    public const SUBSCRIPTION = 'subscription';
    public const SUBSCRIPTION_CREATED = 'subscription_created';
    public const SUBSCRIPTION_UPDATED = 'subscription_updated';
    public const SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    public const SUBSCRIPTION_PAYMENT_SUCCEEDED = 'subscription_payment_succeeded';
}
