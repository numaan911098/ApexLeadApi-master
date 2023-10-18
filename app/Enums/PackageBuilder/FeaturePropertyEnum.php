<?php

namespace App\Enums\PackageBuilder;

use App\Enums\BasicEnum;

abstract class FeaturePropertyEnum extends BasicEnum
{
    public const NO_OF_LEAD_PROOFS = 'NO_OF_LEAD_PROOFS';
    public const NO_OF_PARTIAL_LEADS = 'NO_OF_PARTIAL_LEADS';
    public const NO_OF_LEADS = 'NO_OF_LEADS';
    public const ENABLE_TRUSTEDFORM = 'ENABLE_TRUSTEDFORM';
}
