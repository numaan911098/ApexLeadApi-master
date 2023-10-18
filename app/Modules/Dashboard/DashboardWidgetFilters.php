<?php

namespace App\Modules\Dashboard;

use Carbon\Carbon;
use App\Enums\TimePeriodsEnum;
use Log;

class DashboardWidgetFilters
{
    public const COMPARE_PERIODS = [
        [
            'label' => 'Custom',
            'value' => TimePeriodsEnum::CUSTOM,
        ],
        [
            'label' => 'Previous Period',
            'value' => TimePeriodsEnum::PREVIOUS_PERIOD,
        ],
        [
            'label' => 'Previous Year',
            'value' => TimePeriodsEnum::PREVIOUS_YEAR,
        ]
    ];

    public const PERIODS = [
        [
            'label' => 'Today',
            'value' => TimePeriodsEnum::TODAY,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Yesterday',
            'value' => TimePeriodsEnum::YESTERDAY,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Week to date',
            'value' => TimePeriodsEnum::WEEK_TO_DATE,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Last Week',
            'value' => TimePeriodsEnum::LAST_WEEK,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Month to date',
            'value' => TimePeriodsEnum::MONTH_TO_DATE,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Last Month',
            'value' => TimePeriodsEnum::LAST_MONTH,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Last 3 Months',
            'value' => TimePeriodsEnum::LAST_3_MONTHS,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Last 6 Months',
            'value' => TimePeriodsEnum::LAST_6_MONTHS,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Year to date',
            'value' => TimePeriodsEnum::YEAR_TO_DATE,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'Last Year',
            'value' => TimePeriodsEnum::LAST_YEAR,
            'compare' => self::COMPARE_PERIODS,
        ],
        [
            'label' => 'All Time',
            'value' => TimePeriodsEnum::ALL_TIME,
            'compare' => [],
        ],
        [
            'label' => 'Custom Range',
            'value' => TimePeriodsEnum::CUSTOM,
            'compare' => self::COMPARE_PERIODS,
        ],
    ];

    public static function getPeriodStartEnd($period, Carbon $instance = null)
    {
        $instance = $instance ?? Carbon::now();

        switch ($period) {
            case TimePeriodsEnum::TODAY:
                $startDate = $instance->startOfDay();
                $endDate = $instance->copy()->endOfDay();
                break;
            case TimePeriodsEnum::YESTERDAY:
                $startDate = $instance->subDay()->startOfDay();
                $endDate = $instance->copy()->endOfDay();
                break;
            case TimePeriodsEnum::WEEK_TO_DATE:
                $startDate = $instance->copy()->startOfWeek();
                $endDate = $instance->today()->endOfDay();
                break;
            case TimePeriodsEnum::LAST_WEEK:
                $startDate = $instance->startOfWeek()->subWeek();
                $endDate = $instance->copy()->endOfWeek();
                break;
            case TimePeriodsEnum::MONTH_TO_DATE:
                $startDate = $instance->startOfMonth();
                $endDate = $instance->today()->endOfDay();
                break;
            case TimePeriodsEnum::LAST_MONTH:
                $instance->month = $instance->month - 1;
                $startDate = $instance->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                break;
            case TimePeriodsEnum::LAST_3_MONTHS:
                $instance->month = $instance->month - 3;
                $startDate = $instance->startOfMonth();
                $endDate = $startDate->copy();
                $endDate->month = $endDate->month + 2;
                $endDate = $endDate->endOfMonth();
                break;
            case TimePeriodsEnum::LAST_6_MONTHS:
                $instance->month = $instance->month - 6;
                $startDate = $instance->startOfMonth();
                $endDate = $startDate->copy();
                $endDate->month = $endDate->month + 5;
                $endDate = $endDate->endOfMonth();
                break;
            case TimePeriodsEnum::YEAR_TO_DATE:
                $startDate = $instance->startOfYear();
                $endDate = $instance->today()->endOfDay();
                break;
            case TimePeriodsEnum::LAST_YEAR:
                $instance->year = $instance->year - 1;
                $startDate = $instance->startOfYear();
                $endDate = $startDate->copy()->endOfYear();
                break;
            default:
                $startDate = null;
                $endDate = null;
                $instance = false;
        }

        return [$startDate, $endDate];
    }

    public static function getComparePeriodStartEnd($period, $comparePeriod)
    {
        if (TimePeriodsEnum::PREVIOUS_PERIOD === $comparePeriod) {
            $instance = self::getPreviousPeriodInstance($period);
            if (!empty($instance)) {
                $startEndPeriod = self::getPeriodStartEnd($period, $instance);

                // Reset now
                Carbon::setTestNow();

                return $startEndPeriod;
            }
        } elseif (TimePeriodsEnum::PREVIOUS_YEAR === $comparePeriod) {
            $currentYear = Carbon::now();

            $prevYear = $currentYear->copy();
            $prevYear->year = $currentYear->year - 1;
            $prevYear->month = $currentYear->month;
            if ($prevYear->daysInMonth < $currentYear->day) {
                $prevYear->endOfMonth();
            } else {
                $prevYear->day = $currentYear->day;
            }
            $prevYear->hour = $currentYear->hour;
            $prevYear->minute = $currentYear->minute;
            $prevYear->second = $currentYear->second;
            $prevYear->setTestNow($prevYear);

            if (!empty($prevYear)) {
                $startEndPeriod = self::getPeriodStartEnd($period, $prevYear);

                // Reset now.
                Carbon::setTestNow();

                return $startEndPeriod;
            }
        }

        return false;
    }

    public static function getPreviousPeriodInstance($period, Carbon $instance = null)
    {
        $instance = $instance ?? Carbon::now();

        switch ($period) {
            case TimePeriodsEnum::TODAY:
                $instance->setTestNow($instance->yesterday());
                break;
            case TimePeriodsEnum::YESTERDAY:
                $instance->setTestNow($instance->yesterday());
                break;
            case TimePeriodsEnum::WEEK_TO_DATE:
                $instance->setTestNow($instance->subWeek());
                break;
            case TimePeriodsEnum::LAST_WEEK:
                $instance->setTestNow($instance->subWeek());
                break;
            case TimePeriodsEnum::MONTH_TO_DATE:
                $day = $instance->day;
                $instance->month = $instance->month - 1;
                if ($instance->daysInMonth < $day) {
                    $instance->endOfMonth();
                } else {
                    $instance->day = $day;
                }
                $instance->setTestNow($instance);
                break;
            case TimePeriodsEnum::LAST_MONTH:
                $instance->setTestNow($instance->startOfMonth()->subMonth());
                break;
            case TimePeriodsEnum::LAST_3_MONTHS:
                $instance->setTestNow($instance->startOfMonth()->subMonth(3));
                break;
            case TimePeriodsEnum::LAST_6_MONTHS:
                $instance->setTestNow($instance->startOfMonth()->subMonth(6));
                break;
            case TimePeriodsEnum::YEAR_TO_DATE:
                $day = $instance->day;
                $month = $instance->month;
                $instance->year = $instance->year - 1;
                $instance->month = $month;
                if ($instance->daysInMonth < $day) {
                    $instance->endOfMonth();
                } else {
                    $instance->day = $day;
                }
                $instance->setTestNow($instance->endOfDay());
                break;
            case TimePeriodsEnum::LAST_YEAR:
                $instance->setTestNow($instance->startOfYear()->subYear());
                break;
            default:
                $instance = false;
        }

        return Carbon::now();
    }
}
