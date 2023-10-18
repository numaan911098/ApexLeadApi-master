<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class FormThemeTemplateTypesEnum extends BasicEnum
{
    public const DEFAULT = 'DEFAULT';
    public const SHARED = 'SHARED';
    public const CUSTOM = 'CUSTOM';
}
