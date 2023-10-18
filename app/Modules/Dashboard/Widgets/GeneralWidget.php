<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Dashboard\DashboardWidgetFilters;
use App\Enums\TimePeriodsEnum;
use App\Enums\WidgetTypesEnum;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Form;
use Auth;
use Log;

class GeneralWidget
{
    /**
     * Widget handler.
     *
     * @param array $params Widget Params.
     * @return array
     */
    public function handler(array $widget)
    {
        $forms = Auth::user()->forms()->select(['id', 'title'])->get();
        $formIds = $forms->pluck('id');

        $filters = [
            'forms' => $forms->toArray(),
            'periods' => DashboardWidgetFilters::PERIODS,
        ];

        if (isset($widget['params']) && isset($widget['params']['filter_params'])) {
            $filterParams = $widget['params']['filter_params'];
            if (!isset($filterParams['forms'])) {
                $filterParams['forms']['ids'] = [];
            } else {
                foreach ($filterParams['forms']['ids'] as &$id) {
                    $id = (int) $id;
                }
            }
        } else {
            $filterParams = [
                'forms' => ['ids' => []],
                'period' => [
                    'value' => TimePeriodsEnum::MONTH_TO_DATE,
                ],
            ];
        }

        list($periodStartDate, $periodEndDate) = $this->getPeriodStartEnd($filterParams, false);
        $filterParams['period']['start_date'] = $periodStartDate;
        $filterParams['period']['end_date'] = $periodEndDate;

        if (
            isset($filterParams['compare_period']) &&
            $filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME
        ) {
            list($comparePeriodStartDate, $comparePeriodEndDate) = $this->getPeriodStartEnd($filterParams, true);
            $filterParams['compare_period']['start_date'] = $comparePeriodStartDate;
            $filterParams['compare_period']['end_date'] = $comparePeriodEndDate;
        }

        $response = [
            'type' => WidgetTypesEnum::GENERAL,
            'filters' => $filters,
            'response' => [
                'views' => $this->views($filterParams, $formIds),
                'leads' => $this->leads($filterParams, $formIds),
                'partial_leads' => $this->partialLeads($filterParams, $formIds),
                'conversion_rate' => $this->conversionRate($filterParams, $formIds),
                'completion_time' => $this->averageCompletionTime($filterParams, $formIds),
            ],
        ];

        if ($filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME) {
            // convert carbon instance to string
            $filterParams['period']['start_date'] = $periodStartDate->toDateString();
            $filterParams['period']['end_date'] = $periodEndDate->toDateString();
            if (isset($filterParams['compare_period'])) {
                $filterParams['compare_period']['start_date'] = $comparePeriodStartDate->toDateString();
                $filterParams['compare_period']['end_date'] = $comparePeriodEndDate->toDateString();
            }
        }

        $response['filter_params'] = $filterParams;
        return $response;
    }

    /**
     * Calculate views for general widget.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function views(array $filterParams, $formIds)
    {
        $result = $this->viewsByPeriod($filterParams, $formIds);
        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = $this->viewsByPeriod($filterParams, $formIds, true);
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    /**
     * Calculate views for a specific period.
     *
     * @param array $filterParams Filter params
     * @param Collection $formIds Collection of Form Ids.
     * @param boolean $compare Calculate results for compare period if true.
     * @return void
     */
    public function viewsByPeriod(array $filterParams, $formIds, $compare = false)
    {
        $countBy = 'days';
        $result = [
            'total' => 0,
            'value' => [],
            'unit'  => $countBy,
        ];
        if (!empty($filterParams['forms']['ids'])) {
            $formIds = collect($filterParams['forms']['ids']);
        }

        list($startDate, $endDate) = $this->getFilterParamStartEndDate($filterParams, $compare);

        if (
            $filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME &&
            $startDate->diffInDays($endDate) <= 1
        ) {
            $countBy = 'hours';
        }

        $formViews = Form::formVisitsQuery($formIds->toArray(), $startDate, $endDate, true, $countBy);
        $result['total'] = $formViews->sum('visits');
        $result['value'] = $formViews;
        $result['unit'] = $countBy;
        return $result;
    }

    /**
     * Calculate conversions for general widget.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function conversions(array $filterParams, $formIds)
    {
        $result = $this->conversionsByPeriod($filterParams, $formIds);
        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = $this->conversionsByPeriod($filterParams, $formIds, true);
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    /**
     * Calculate conversions for a specific period.
     *
     * @param array $filterParams Filter params
     * @param Collection $formIds Collection of Form Ids.
     * @param boolean $compare Calculate results for compare period if true.
     * @return void
     */
    public function conversionsByPeriod(array $filterParams, $formIds, $compare = false)
    {
        $countBy = 'days';
        $result = [
            'total' => 0,
            'value' => [],
            'unit'  => $countBy,
        ];
        if (!empty($filterParams['forms']['ids'])) {
            $formIds = collect($filterParams['forms']['ids']);
        }

        list($startDate, $endDate) = $this->getFilterParamStartEndDate($filterParams, $compare);

        if (
            $filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME &&
            $startDate->diffInDays($endDate) <= 1
        ) {
            $countBy = 'hours';
        }

        $formConversions = Form::formConversionsQuery($formIds->toArray(), $startDate, $endDate, true, $countBy);
        $result['total'] = $formConversions->sum('conversions');
        $result['value'] = $formConversions;
        $result['unit'] = $countBy;
        return $result;
    }

    /**
     * Calculate leads for general widget.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function leads(array $filterParams, $formIds)
    {
        $result = $this->leadsByPeriod($filterParams, $formIds);
        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = $this->leadsByPeriod($filterParams, $formIds, true);
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    /**
     * Calculate leads for a specific period.
     *
     * @param array $filterParams Filter params
     * @param Collection $formIds Collection of Form Ids.
     * @param boolean $compare Calculate results for compare period if true.
     * @return void
     */
    public function leadsByPeriod(array $filterParams, $formIds, $compare = false)
    {
        $countBy = 'days';
        $result = [
            'total' => 0,
            'value' => [],
            'unit' => $countBy,
        ];
        if (!empty($filterParams['forms']['ids'])) {
            $formIds = collect($filterParams['forms']['ids']);
        }

        list($startDate, $endDate) = $this->getFilterParamStartEndDate($filterParams, $compare);

        if (
            $filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME &&
            $startDate->diffInDays($endDate) <= 1
        ) {
            $countBy = 'hours';
        }

        $formLeads = Form::formLeadsQuery($formIds->toArray(), $startDate, $endDate, true, $countBy);
        $result['total'] = $formLeads->sum('leads');
        $result['value'] = $formLeads;
        $result['unit'] = $countBy;
        return $result;
    }

    /**
     * Calculate  Partial leads for general widget.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function partialLeads(array $filterParams, $formIds)
    {
        $result = $this->partialLeadsByPeriod($filterParams, $formIds);
        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = $this->partialLeadsByPeriod($filterParams, $formIds, true);
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    public function partialLeadsByPeriod(array $filterParams, $formIds, $compare = false)
    {
        $countBy = 'days';
        $result = [
            'total' => 0,
            'value' => [],
            'unit' => $countBy,
        ];
        if (!empty($filterParams['forms']['ids'])) {
            $formIds = collect($filterParams['forms']['ids']);
        }

        list($startDate, $endDate) = $this->getFilterParamStartEndDate($filterParams, $compare);

        if (
            $filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME &&
            $startDate->diffInDays($endDate) <= 1
        ) {
            $countBy = 'hours';
        }

        $formLeads = Form::formPartialLeadsQuery($formIds->toArray(), $startDate, $endDate, true, $countBy);
        $result['total'] = $formLeads->sum('leads');
        $result['value'] = $formLeads;
        $result['unit'] = $countBy;
        return $result;
    }

    /**
     * Calculate visitors for general widget.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function visitors(array $filterParams, $formIds)
    {
        $result = $this->visitorsByPeriod($filterParams, $formIds);
        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = $this->visitorsByPeriod($filterParams, $formIds, true);
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    /**
     * Calculate visitors for a specific period.
     *
     * @param array $filterParams Filter params
     * @param Collection $formIds Collection of Form Ids.
     * @param boolean $compare Calculate results for compare period if true.
     * @return void
     */
    public function visitorsByPeriod(array $filterParams, $formIds, $compare = false)
    {
        $countBy = 'days';
        $result = [
            'total' => 0,
            'value' => [],
            'unit' => $countBy,
        ];
        if (!empty($filterParams['forms']['ids'])) {
            $formIds = collect($filterParams['forms']['ids']);
        }

        list($startDate, $endDate) = $this->getFilterParamStartEndDate($filterParams, $compare);

        if (
            $filterParams['period']['value'] !== TimePeriodsEnum::ALL_TIME &&
            $startDate->diffInDays($endDate) <= 1
        ) {
            $countBy = 'hours';
        }

        $formVisitors = Form::formVisitorsQuery($formIds->toArray(), $startDate, $endDate, true, $countBy);
        $result['total'] = $formVisitors->sum('visitors');
        $result['value'] = $formVisitors;
        $result['unit'] = $countBy;
        return $result;
    }

    /**
     * Calculate conversion rate for general widget.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function conversionRate(array $filterParams, $formIds)
    {
        $result = [
            'value' => collect([]),
        ];
        $visitors = $this->visitors($filterParams, $formIds);
        $conversions = $this->conversions($filterParams, $formIds);
        if ($visitors['total'] > 0) {
            $result['total'] = round($conversions['total'] / $visitors['total'] * 100, 2);
        } else {
            $result['total'] = 0;
        }

        foreach ($conversions['value'] as $index => $value) {
            if ($value->conversions > 0) {
                $visitorsValueItem = $visitors['value']
                    ->where('visitors_created_at', $value->conversions_created_at)
                    ->first();
                if ($visitorsValueItem) {
                    if ($value->conversions > $visitorsValueItem->visitors) {
                        $conversionRate = 100;
                    } else {
                        $conversionRate = round($value->conversions / $visitorsValueItem->visitors * 100, 2);
                    }
                } else {
                    $conversionRate = 100;
                }
            } else {
                $conversionRate = 0;
            }
            $result['value']->push((object) [
                'conversion_rate' => $conversionRate,
                'conversion_rate_created_at' => $value->conversions_created_at
            ]);
        }

        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = [
            'value' => collect([]),
        ];
        foreach ($conversions['compare']['value'] as $index => $value) {
            if ($value->conversions > 0) {
                $visitorsValueItem = $visitors['compare']['value']
                    ->where('visitors_created_at', $value->conversions_created_at)
                    ->first();
                if ($visitorsValueItem) {
                    if ($value->conversions > $visitorsValueItem->visitors) {
                        $conversionRate = 100;
                    } else {
                        $conversionRate = round($value->conversions / $visitorsValueItem->visitors * 100, 2);
                    }
                } else {
                    $conversionRate = 100;
                }
            } else {
                $conversionRate = 0;
            }
            $result['compare']['value']->push((object) [
                'conversion_rate' => $conversionRate,
                'conversion_rate_created_at' => $value->conversions_created_at
            ]);
        }

        if ($visitors['compare']['total'] > 0) {
            $result['compare']['total'] = round(
                $conversions['compare']['total'] / $visitors['compare']['total'] * 100,
                2
            );
        } else {
            $result['compare']['total'] = 0;
        }

        $result['unit'] = $visitors['unit'];
        $result['compare']['unit'] = $visitors['unit'];
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    /**
     * Calculate form lead average completion time.
     *
     * @param array $filterParams
     * @param Collection $formIds
     * @return void
     */
    public function averageCompletionTime(array $filterParams, $formIds)
    {
        $result = $this->averageCompletionTimeByPeriod($filterParams, $formIds);
        if (empty($filterParams['compare_period'])) {
            return $result;
        }

        $result['compare'] = $this->averageCompletionTimeByPeriod($filterParams, $formIds, true);
        $result['total_percentage_change'] = $this->percentageChange($result['total'], $result['compare']['total']);
        return $result;
    }

    /**
     * Calculate lead completion average for a specific period.
     *
     * @param array $filterParams Filter params
     * @param Collection $formIds Collection of Form Ids.
     * @param boolean $compare Calculate results for compare period if true.
     * @return void
     */
    public function averageCompletionTimeByPeriod(array $filterParams, $formIds, $compare = false)
    {
        $result = [
            'total' => 0,
        ];
        if (!empty($filterParams['forms']['ids'])) {
            $formIds = collect($filterParams['forms']['ids']);
        }

        list($startDate, $endDate) = $this->getFilterParamStartEndDate($filterParams, $compare);
        $result['total'] = Form::formAverageCompletionQuery($formIds->toArray(), $startDate, $endDate);
        return $result;
    }

    private function getPeriodStartEnd($filterParams, $compare)
    {
        $period = $filterParams['period'];
        if ($compare) {
            $comparePeriod = $filterParams['compare_period'];
            if ($comparePeriod['value'] === TimePeriodsEnum::CUSTOM) {
                $startDate = Carbon::createFromFormat('Y-m-d', $comparePeriod['start_date']);
                $endDate = Carbon::createFromFormat('Y-m-d', $comparePeriod['end_date']);
            } else {
                if (
                    $period['value'] === TimePeriodsEnum::CUSTOM &&
                    $comparePeriod['value'] === TimePeriodsEnum::PREVIOUS_PERIOD
                ) {
                    $startDate = $period['start_date']->copy();
                    $endDate = $period['end_date']->copy();
                    $diffInDays = $startDate->diffInDays($endDate);
                    $endDate = $startDate->copy();
                    $endDate->day = $endDate->day - 1;
                    $startDate->day -= $diffInDays <= 0 ? 1 : $diffInDays + 1;
                } elseif (
                    $period['value'] === TimePeriodsEnum::CUSTOM &&
                    $comparePeriod['value'] === TimePeriodsEnum::PREVIOUS_YEAR
                ) {
                    $startDate = $period['start_date']->copy();
                    $startDate->year = $startDate->year - 1;
                    $endDate = $period['end_date']->copy();
                    $endDate->year = $endDate->year - 1;
                } else {
                    $startEndDate = DashboardWidgetFilters::getComparePeriodStartEnd(
                        $period['value'],
                        $comparePeriod['value']
                    );
                    $startDate = $startEndDate[0];
                    $endDate = $startEndDate[1];
                }
            }
        } else {
            if ($period['value'] === TimePeriodsEnum::CUSTOM) {
                $startDate = Carbon::createFromFormat('Y-m-d', $period['start_date']);
                $endDate = Carbon::createFromFormat('Y-m-d', $period['end_date']);
            } else {
                $startEndDate = DashboardWidgetFilters::getPeriodStartEnd($period['value']);
                $startDate = $startEndDate[0];
                $endDate = $startEndDate[1];
            }
        }

        return [$startDate, $endDate];
    }

    private function percentageChange($value1, $value2)
    {
        if (intval($value2) === 0) {
            return 0;
        }

        return round((($value1 - $value2) / $value2) * 100, 2);
    }

    private function getFilterParamStartEndDate(array $filterParams, $compare)
    {
        if ($compare) {
            return [
                $filterParams['compare_period']['start_date'],
                $filterParams['compare_period']['end_date'],
            ];
        }

        return [$filterParams['period']['start_date'], $filterParams['period']['end_date']];
    }
}
