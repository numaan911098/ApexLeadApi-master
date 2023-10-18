<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class HttpVerbsEnum extends BasicEnum
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
}
