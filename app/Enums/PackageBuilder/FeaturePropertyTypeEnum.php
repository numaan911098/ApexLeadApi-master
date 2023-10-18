<?php

namespace App\Enums\PackageBuilder;

use App\Enums\BasicEnum;

abstract class FeaturePropertyTypeEnum extends BasicEnum
{
    public const NUMBER = 'number';
    public const TEXT = 'text';
    public const TEXTAREA = 'textarea';
    public const BOOLEAN = 'boolean';
}
