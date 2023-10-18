<?php

namespace App\Enums\Form;

use App\Enums\BasicEnum;

abstract class FormTrackingEventTypesEnum extends BasicEnum
{
    public const TRUSTEDFORM = 'TRUSTEDFORM';
    public const LOADED = 'LOADED';
    public const INTERACTED = 'INTERACTED';
    public const STEP_CHANGED = 'STEP_CHANGED';
    public const STEP_COMPLETED = 'STEP_COMPLETED';
    public const SUBMIT = 'SUBMIT';
    public const SUBMIT_SUCCESS = 'SUBMIT_SUCCESS';
    public const SUBMIT_FAILED = 'SUBMIT_FAILED';
}
