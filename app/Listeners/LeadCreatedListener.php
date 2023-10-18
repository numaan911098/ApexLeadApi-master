<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Enums\DashboardsEnum;
use Cache;

class LeadCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  LeadCreated  $event
     * @return void
     */
    public function handle(LeadCreated $event)
    {
        $lead = $event->formLead;
        $form = $lead->form;

        $dashboardKey = 'user_' . $form->created_by . '.dashboards_' . DashboardsEnum::FORMLEADS_VS_TIME;

        Cache::forget($dashboardKey);
    }
}
