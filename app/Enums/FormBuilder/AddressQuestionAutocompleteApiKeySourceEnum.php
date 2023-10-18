<?php

namespace App\Enums\FormBuilder;

use App\Enums\BasicEnum;

abstract class AddressQuestionAutocompleteApiKeySourceEnum extends BasicEnum
{
    public const CUSTOM = 'custom';
    public const GLOBAL_API_KEY = 'globalApiKey';
}
