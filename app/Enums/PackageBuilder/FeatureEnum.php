<?php

namespace App\Enums\PackageBuilder;

use App\Enums\BasicEnum;

abstract class FeatureEnum extends BasicEnum
{
    public const LEAD_PROOFS = 'lead_proofs';
    public const PARTIAL_LEADS = 'partial_leads';
    public const LEADS = 'leads';
    public const TRUSTEDFORM = 'trustedform';
}
