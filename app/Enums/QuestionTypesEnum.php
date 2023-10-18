<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class QuestionTypesEnum extends BasicEnum
{
    public const SHORT_TEXT = "SHORT_TEXT";
    public const MULTIPLE_CHOICE = "MULTIPLE_CHOICE";
    public const SINGLE_CHOICE = "SINGLE_CHOICE";
    public const PARAGRAPH_TEXT = "PARAGRAPH_TEXT";
    public const EMAIL_ADDRESS = "EMAIL_ADDRESS";
    public const PHONE_NUMBER = "PHONE_NUMBER";
    public const GDPR = "GDPR";
    public const DATE = 'DATE';
    public const FIRST_NAME = 'FIRST_NAME';
    public const LAST_NAME = 'LAST_NAME';
    public const ADDRESS = 'ADDRESS';
    public const NUMBER = 'NUMBER';
    public const URL = "URL";
    public const RANGE = 'RANGE';
}
