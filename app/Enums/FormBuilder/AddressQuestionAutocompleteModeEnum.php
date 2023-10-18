<?php

namespace App\Enums\FormBuilder;

use App\Enums\BasicEnum;

abstract class AddressQuestionAutocompleteModeEnum extends BasicEnum
{
    public const MANUAL = 'manual';
    public const SEARCH = 'search';
}
