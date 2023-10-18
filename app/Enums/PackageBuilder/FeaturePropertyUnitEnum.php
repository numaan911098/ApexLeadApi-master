<?php

namespace App\Enums\PackageBuilder;

use App\Enums\BasicEnum;

abstract class FeaturePropertyUnitEnum extends BasicEnum
{
    public const UNIT_NO_OF_LEAD_PROOFS = 'number';
    public const UNIT_NO_OF_PARTIAL_LEADS = 'number';
    public const UNIT_NO_OF_LEADS = 'number';
    public const UNIT_BOOLEAN = 'boolean';
}
