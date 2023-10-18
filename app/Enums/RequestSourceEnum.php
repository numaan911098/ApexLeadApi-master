<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class RequestSourceEnum extends BasicEnum
{
    public const SOURCE_ZAPIER =  'zapier';
    public const SOURCE_COMMAND =  'command';
    public const SOURCE_MANUAL =  'manual';
    public const SOURCE_MANUAL_COMMAND =  'manual_command';
}
