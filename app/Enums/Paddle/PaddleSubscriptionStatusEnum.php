<?php

namespace App\Enums\Paddle;

use App\Enums\BasicEnum;

abstract class PaddleSubscriptionStatusEnum extends BasicEnum
{
    public const ACTIVE    = 'active';
    public const TRIALING  = 'trialing';
    public const PAST_DUE  = 'past_due';
    public const PAUSED    = 'paused';
    public const DELETED   = 'deleted';
}
