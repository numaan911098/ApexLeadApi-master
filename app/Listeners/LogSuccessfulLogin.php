<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\LoginHistory;
use Illuminate\Support\Facades\Request;
use Facades\App\Services\Util;
use Agent;
use Sentry;

class LogSuccessfulLogin
{

    protected $loginHistory;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(LoginHistory $history)
    {
        $this->loginHistory = $history;
    }

    /**
     * Handle the event.
     *
     * @param  UserLoggedIn  $event
     * @return void
     */
    public function handle(UserLoggedIn $event)
    {
        try {
            $geolocation = Util::geolocation(Request::ip());
            $this->loginHistory->create([
                'user_id' => $event->user->id,
                'attempted_at' => now(),
                'browser' => Agent::browser(),
                'device' => Util::deviceType(),
                'ip'    =>  Request::ip(),
                'os' => Agent::platform(),
                'country' => $geolocation['geoplugin_countryName'],
                'state' => $geolocation['geoplugin_regionName'],
                'city' => $geolocation['geoplugin_city']
            ]);
        } catch (\Exception $e) {
            Sentry\captureException($e);
        }
    }
}
