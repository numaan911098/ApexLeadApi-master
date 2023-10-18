<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ShowDashboardRequest;
use App\Enums\DashboardWidgetTypesEnum;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\DashboardsEnum;
use App\Modules\Dashboard\DashboardManager;
use Carbon\Carbon;
use App\Form;
use Auth;
use DB;
use Cache;

class DashboardController extends Controller
{
    /**
     * Dashboard Service instance.
     *
     * @var DashboardManager
     */
    protected $dashboardMgr;

    public function __construct(DashboardManager $dashboardMgr)
    {
        $this->middleware('jwt.auth');

        $this->dashboardMgr = $dashboardMgr;
    }

    /**
     * Generate Dashboard  widget report.
     *
     * @param Request $request HTTP request instance.
     * @param string  $dashboard Dashboard Type.
     * @return void
     */
    public function widget(ShowDashboardRequest $request, $widget)
    {
        $data = $request->input('widget');

        if ($widget === DashboardWidgetTypesEnum::GENERAL) {
            $response = $this->dashboardMgr->generalWidget($data);
            return $this->managerResponse($response);
        }

        return $this->apiResponse(
            400,
            [],
            ErrorTypes::INVALID_DASHBOARD_WIDGET_TYPE,
            'Invalid dashboard widget type.'
        );
    }

    public function formleadsVsTime(Request $request)
    {
        $dashboardKey = 'user_' . Auth::id() . '.dashboards_' . DashboardsEnum::FORMLEADS_VS_TIME;

        DB::statement(DB::raw('set @inc := 0, @prev := ""'));
        $resultSet = Form::where('created_by', Auth::user()->id)
            ->join('form_leads', 'forms.id', '=', 'form_leads.form_id')
            ->select(
                DB::raw('@inc := if(@prev <> forms.id, @inc + 1, @inc) as lead_form_index'),
                DB::raw('@prev := forms.id as prev_form_id'),
                'forms.title as lead_form_title',
                'forms.id as lead_form_id',
                DB::raw('DAY(form_leads.created_at) AS lead_created_at_day'),
                DB::raw('COUNT(*) AS leads')
            )
            ->where(DB::raw('MONTH(form_leads.created_at)'), Carbon::now()->month)
            ->where(DB::raw('YEAR(form_leads.created_at)'), Carbon::now()->year)
            ->groupBy('lead_form_id', 'lead_created_at_day')
            ->having('lead_form_index', '<=', 10)
            ->orderBy('leads', 'desc')
            ->get();

        $forms = [];
        $formIds = [];
        if (!Cache::has($dashboardKey)) {
            // insert forms with leads
            foreach ($resultSet as $row) {
                if (!array_key_exists($row['lead_form_id'], $forms)) {
                    $formIds[] = $row['lead_form_id'];
                    $forms[$row['lead_form_id']] = [
                        'title' => $row['lead_form_title'],
                        'id' => $row['lead_form_id'],
                        'leads_by_days' => [$row['lead_created_at_day'] => $row['leads']]
                    ];
                } else {
                    array_push(
                        $forms[$row['lead_form_id']]['leads_by_days'],
                        [$row['lead_created_at_day'] => $row['leads']]
                    );
                }
            }
            // insert extra forms without leads
            if (count($forms) <= 10) {
                $extraForms = Form::where('created_by', Auth::id())
                    ->whereNotIn('id', $formIds)
                    ->take(10 - count($forms))
                    ->get();
                foreach ($extraForms as $extraForm) {
                    $forms[$extraForm->id] = [
                        'title' => $extraForm->title,
                        'id' => $extraForm->id,
                        'leads_by_days' => []
                    ];
                }
            }

            Cache::put($dashboardKey, json_encode($forms), 15 * 60);
        } else {
            $forms = json_decode(Cache::get($dashboardKey), true);
        }

        return $this->apiResponse(200, array_values($forms));
    }
}
