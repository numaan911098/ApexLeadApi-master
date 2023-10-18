<?php

namespace App\Services;

use App\Enums\ConfigKeyEnum;
use App\Form;
use App\FormVisit;
use App\User;
use App\Enums\RolesEnum;
use Facades\App\Services\Util;
use Carbon\Carbon;
use DB;
use App\PaddleSubscription;
use App\Subscription;

/**
 * Generate Leadgen System Report.
 */
class LeadgenReportService
{
    /**
     * Slack webhook URL.
     *
     * @var string
     */
    private $slackWebhookUrl;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * @var Carbon instance.
     */
    private Carbon $carbon;

    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
        $this->slackWebhookUrl = config(ConfigKeyEnum::LEADGEN_SLACK_REPORT_CHANNEL);
    }

    /**
     * Send report to destination.
     *
     * @return void
     */
    public function report()
    {
        $report = [];
        $report[] = $this->totalFormsReport();
        $report[] = $this->formCreateReport();
        $report[] = $this->leadsReport();
        $report[] = $this->viewsReport();
        $report[] = $this->visitorsReport();
        $report[] = $this->visitorsFromMobileReport();
        $report[] = $this->visitorsFromDesktopReport();
        $report[] = $this->experimentsRunningReport();
        $report[] = $this->userAccountsReport(RolesEnum::CUSTOMER);
        $report[] = $this->userAccountsReport(RolesEnum::SUPER_CUSTOMER);
        $report[] = $this->userAccountsReport(RolesEnum::ADMIN);
        $report[] = $this->paidPaddleAccountsReport();
        $report[] = $this->paidStripeAccountsReport();
        $report[] = $this->trialAccountsReport();
        $report[] = $this->recentlyCancelledReport();

        Util::apiCall($this->slackWebhookUrl, 'post', [
            'json' => [
                'username' => 'Leadgen BOT',
                'attachments' => [
                    [
                        'pretext' => 'A daily report from leadgen system.',
                        'title' => 'Leadgen Daily Report.',
                        'color' => '#ee6e73',
                        'title_link' => 'https://leadgenapp.io',
                        'fallback' => 'Leadgen Daily Report.',
                        'fields' => $this->reportToSlackFields($report),
                        'footer' => 'Powered by leadgen',
                        'ts' => time(),
                    ]
                ]
            ]
        ]);
    }

    /**
     * Transform report to slack webhook fields.
     *
     * @param array $report Array of reports.
     * @return void
     */
    public function reportToSlackFields(array $reports)
    {
        $fields = [];
        foreach ($reports as $report) {
            $fields[] = [
                'title' => $report['title'],
                'value' => $report['count'],
                'short' => false,
            ];
        }

        return $fields;
    }

    /**
     * Report for total number of forms untill today.
     *
     * @return void
     */
    public function totalFormsReport()
    {
        $count = Form::count();
        return [
            'title' => 'Total Forms',
            'count' => $count,
        ];
    }

    /**
     * Report for number of forms created today.
     *
     * @return void
     */
    public function formCreateReport()
    {
        $count = Form::whereDate('created_at', Carbon::now()->toDateString())->count();
        return [
            'title' => 'Forms created',
            'count' => $count,
        ];
    }

    /**
     * Report for number of views captured today.
     *
     * @return void
     */
    public function viewsReport()
    {
        $count = FormVisit::whereDate('created_at', Carbon::now()->toDateString())->count();
        return [
            'title' => 'Views captured',
            'count' => $count,
        ];
    }

    /**
     * Report for no. of visitors.
     *
     * @return void
     */
    public function visitorsReport()
    {
        $sub = DB::table('form_visits')
            ->whereDate('form_visits.created_at', Carbon::now()->toDateString())
            ->groupBy('form_visits.visitor_id');
        $count = DB::table(DB::raw("({$sub->toSql()}) as sub"))
            ->mergeBindings($sub)
            ->count();
        return [
            'title' => 'Visitors captured',
            'count' => $count,
        ];
    }

    /**
     * Report for no. of mobile visitors.
     *
     * @return void
     */
    public function visitorsFromMobileReport()
    {
        $sub = DB::table('form_visits')
            ->whereDate('form_visits.created_at', Carbon::now()->toDateString())
            ->where('form_visits.device_type', 'MOBILE')
            ->groupBy('form_visits.visitor_id');
        $count = DB::table(DB::raw("({$sub->toSql()}) as sub"))
            ->mergeBindings($sub)
            ->count();
        return [
            'title' => 'Mobile Visitors captured',
            'count' => $count,
        ];
    }

    /**
     * Report for no. of desktop visitors.
     *
     * @return void
     */
    public function visitorsFromDesktopReport()
    {
        $sub = DB::table('form_visits')
            ->whereDate('form_visits.created_at', Carbon::now()->toDateString())
            ->where('form_visits.device_type', 'DESKTOP')
            ->groupBy('form_visits.visitor_id');
        $count = DB::table(DB::raw("({$sub->toSql()}) as sub"))
            ->mergeBindings($sub)
            ->count();
        return [
            'title' => 'Desktop Visitors captured',
            'count' => $count,
        ];
    }

    /**
     * Report for number of leads captured today.
     *
     * @return void
     */
    public function leadsReport()
    {
        $sub = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->select('form_visits.visitor_id')
            ->whereDate('form_leads.created_at', Carbon::now()->toDateString())
            ->groupBy('form_visits.visitor_id');
        $count = DB::table(DB::raw("({$sub->toSql()}) as sub"))
            ->mergeBindings($sub)
            ->count();
        return [
            'title' => 'Leads captured (Multiple submission from same visitor will be treated as 1 lead)',
            'count' => $count,
        ];
    }

    /**
     * Report for number of experiments running.
     *
     * @return void
     */
    public function experimentsRunningReport()
    {
        $count = DB::table('form_experiments')
            ->whereNotNull('form_experiments.started_at')
            ->whereNull('form_experiments.ended_at')
            ->count();
        return [
            'title' => 'Experiments running',
            'count' => $count,
        ];
    }

    /**
     * Report for number of users with role.
     *
     * @param mixed $role User role.
     * @return void
     */
    public function userAccountsReport($role)
    {
        $count = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', $role)
            ->whereNull('users.deleted_at')
            ->count();
        return [
            'title' => 'Users with ' . $role . ' role',
            'count' => $count,
        ];
    }


    /**
     * Report for number of paid users.
     *
     * @return void
     */
    public function paidPaddleAccountsReport()
    {
        $countPaddle = PaddleSubscription::where('status', 'active')->count();
        return [
            'title' => 'Active Paddle Subscribers ',
            'count' => $countPaddle,
        ];
    }
    /**
     * Report for number of paid users.
     *
     * @return void
     */
    public function paidStripeAccountsReport()
    {
        $countStripe = Subscription::where('stripe_status', 'active')
            ->where(function ($query) {
                $query->where('ends_at', '>=', $this->carbon->now()->toDateTimeString())
                    ->orWhereNull('ends_at');
            })
            ->count();

        return [
            'title' => 'Active Stripe Subscribers ',
            'count' => $countStripe,
        ];
    }


    /**
     * Report for number of trial users.
     *
     * @return void
     */
    public function trialAccountsReport()
    {
        $countPaddle = PaddleSubscription::where('status', 'trialing')->count();
        $countStripe = Subscription::where('stripe_status', 'trialing')->count();
        $count = $countPaddle + $countStripe;
        return [
            'title' => 'Trial Subscribers ',
            'count' => $count,
        ];
    }

    /**
     * Report for number of recently cancelled users.
     *
     * @return void
     */
    public function recentlyCancelledReport()
    {
        $recentlyCancelled = User::all()->filter(function ($user) {
            return $user->hasSubscriptionOnGracePeriod();
        })->count();
        return [
            'title' => 'Recently Cancelled ',
            'count' => $recentlyCancelled,
        ];
    }

    /**
     * Get start & end date for selected time period
     * @param string $timePeriod
     * @return array
     */
    public function getTimePeriodDates(string $timePeriod): array
    {
        switch ($timePeriod) {
            case 'today':
                return [
                    'start_date' => Carbon::today()->startOfDay(),
                    'end_date' => Carbon::today()->endOfDay(),
                ];
            case 'yesterday':
                return [
                    'start_date' => Carbon::yesterday()->startOfDay(),
                    'end_date' => Carbon::yesterday()->endOfDay(),
                ];
            case 'this_week':
                return [
                    'start_date' => Carbon::now()->startOfWeek(),
                    'end_date' => Carbon::now()->endOfWeek(),
                ];
            case 'last_week':
                return [
                    'start_date' => Carbon::now()->startOfWeek()->subWeek(),
                    'end_date' => Carbon::now()->startOfWeek()->subDay(),
                ];
            default:
                // If timeperiod parameter is invalid, default to today
                return [
                    'start_date' => Carbon::today()->startOfDay(),
                    'end_date' => Carbon::today()->endOfDay(),
                ];
        }
    }

    /**
     * Get user form report.
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $timePeriodDates = $this->getTimePeriodDates($data['timePeriod']);
        $startDate = $timePeriodDates['start_date'];
        $endDate = $timePeriodDates['end_date'];

        $sortField = $data['sortField'] === 'created_at' ? 'total_visits' : $data['sortField'];
        $sortDirection = $data['sortDirection'];
        $reportBuilderQuery = Form::with([
            'createdBy:id,email',
            'formVisits:id,form_id',
            'formLeads:id,form_id',
            'formLeadsPartial:id,form_id'
        ])
            ->leftJoin('users', 'users.id', '=', 'forms.created_by')
            ->select([
                'forms.*',
                'users.email',
                DB::raw('(SELECT COUNT(*) FROM form_visits WHERE form_id = forms.id AND created_at BETWEEN ? AND ?)
            as total_visits'),
                DB::raw('(SELECT COUNT(*) FROM form_leads WHERE form_id = forms.id AND
            is_partial = 0 AND created_at BETWEEN ? AND ?) as total_leads'),
                DB::raw('(SELECT COUNT(*) FROM form_leads WHERE form_id = forms.id AND
             is_partial = 1 AND created_at BETWEEN ? AND ?) as total_partial_leads')
            ])
            ->havingRaw('total_visits > 0 OR total_leads > 0 OR total_partial_leads > 0')
            ->orderBy($sortField, $sortDirection);

        foreach ($data['search'] as $key => $value) {
            if (isset($key) && !empty($value)) {
                $column = $key === 'email' ? 'users.email' : $key;
                $reportBuilderQuery->where($column, 'LIKE', '%' . $value . '%');
            }
        }

        $pagination = $reportBuilderQuery
            ->addBinding([
                $startDate, $endDate,
                $startDate, $endDate,
                $startDate, $endDate
            ], 'select')
            ->orderBy($sortField, $sortDirection)
            ->paginate(LeadgenReportService::PER_PAGE, ['*'], 'page', $data['page']);

        $report = $pagination->items();

        foreach ($report as $form) {
            $form->form_title = $form->title;
            $form->user_email = $form->createdBy->email ?? null;
        }

        $pagination = $pagination->toArray();
        unset($pagination['data']);

        return  [
            'data' => $report,
            'pagination' => $pagination
        ];
    }
}
