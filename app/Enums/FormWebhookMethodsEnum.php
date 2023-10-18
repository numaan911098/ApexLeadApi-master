<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class FormWebhookMethodsEnum extends BasicEnum
{
    public const POST = "POST";
    public const PUT = "PUT";
    public const DELETE = "DELETE";
}
