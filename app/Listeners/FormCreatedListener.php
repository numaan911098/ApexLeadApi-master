<?php

namespace App\Listeners;

use App\Events\FormCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use App\Enums\DashboardsEnum;
use Cache;

class FormCreatedListener
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
     * @param  FormCreated  $event
     * @return void
     */
    public function handle(FormCreated $event)
    {
    }
}
