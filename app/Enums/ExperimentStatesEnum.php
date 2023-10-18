<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class ExperimentStatesEnum extends BasicEnum
{
    public const DRAFT = "DRAFT";
    public const RUNNING = "RUNNING";
    public const ENDED = 'ENDED';
}
