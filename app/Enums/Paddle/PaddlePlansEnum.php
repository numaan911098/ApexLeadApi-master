<?php

namespace App\Enums\Paddle;

use App\Enums\BasicEnum;

abstract class PaddlePlansEnum extends BasicEnum
{
    public const PRO = 'paddle_pro';
    public const PRO_ANNUAL = 'paddle_pro_annual';
    public const PRO2 = 'paddle_pro2';
    public const PRO_TRIAL = 'paddle_pro_trial';
    public const SCALE = 'paddle_scale';
    public const SCALE_ANNUAL = 'paddle_scale_annual';
    public const SCALE_TRIAL = 'paddle_scale_trial';
    public const SCALE_ANNUAL_TRIAL = 'paddle_scale_annual_trial';
    public const PRO_ANNUAL_TRIAL = 'paddle_pro_annual_trial';
    public const ENTERPRISE = 'paddle_enterprise';
    public const ENTERPRISE_ANNUAL = 'paddle_enterprise_annual';
    public const ENTERPRISE_TRIAL = 'paddle_enterprise_trial';
    public const ENTERPRISE_ANNUAL_TRIAL = 'paddle_enterprise_annual_trial';
    public const SINGLE_ANNUAL = 'paddle_single_annual';
}
