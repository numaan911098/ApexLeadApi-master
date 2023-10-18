<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class FormExperimentTypesEnum extends BasicEnum
{
    public const AB = "AB";
    public const MULTI_VARIANT = "MULTI_VARIANT";
}
