<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class OperatorsEnum extends BasicEnum
{
    public const EQ = "=";
    public const NEQ = "!=";
    public const LT = "<";
    public const LTE = "<=";
    public const GT = ">";
    public const GTE = ">=";
    public const AND = "AND";
    public const OR = "OR";
}
