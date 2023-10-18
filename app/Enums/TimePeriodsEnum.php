<?php

namespace App\Enums;

use App\Enums\BasicEnum;

abstract class TimePeriodsEnum extends BasicEnum
{
    public const TODAY = "today";
    public const YESTERDAY = 'yesterday';
    public const WEEK_TO_DATE = 'week_to_date';
    public const LAST_WEEK = 'last_week';
    public const MONTH_TO_DATE = 'month_to_date';
    public const LAST_MONTH = 'last_month';
    public const LAST_3_MONTHS = 'last_3_months';
    public const LAST_6_MONTHS = 'last_6_months';
    public const YEAR_TO_DATE = 'year_to_date';
    public const LAST_YEAR = 'last_year';
    public const ALL_TIME = 'all_time';
    public const CUSTOM = 'custom';
    public const PREVIOUS_PERIOD = 'previous_period';
    public const PREVIOUS_YEAR = 'previous_year';
    public const DAYS = 'days';
    public const HOURS = 'hours';
    public const NONE = "NONE";
    public const AS_PER_PLAN = "AS_PER_PLAN";
    public const MONTHLY = "MONTHLY";
    public const YEARLY = "YEARLY";
}
