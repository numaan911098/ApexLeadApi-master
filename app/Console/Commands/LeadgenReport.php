<?php

namespace App\Console\Commands;

use App\Enums\ConfigKeyEnum;
use App\Enums\EnvironmentsEnum;
use Illuminate\Console\Command;
use App\Services\LeadgenReportService;

class LeadgenReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leadgen:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Leadgen Daily Report to Slack Channel';

    /**
     * Leadgen report service instance.
     *
     * @var LeadgenReportService
     */
    protected $leadgenReportService;

    /**
     * Create a new command instance.
     * @param LeadgenReportService $leadgenReportService
     * @return void
     */
    public function __construct(LeadgenReportService $leadgenReportService)
    {
        parent::__construct();
        $this->leadgenReportService = $leadgenReportService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!config(ConfigKeyEnum::ENABLE_SLACK_REPORT)) {
            return;
        }

        $this->leadgenReportService->report();
    }
}
