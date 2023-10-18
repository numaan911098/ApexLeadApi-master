<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Facades\App\Services\Util;
use App\User;
use Log;

class CaptureUserGeolocation
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
     * @param  UserLoggedIn  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;

        if (!empty($user->ip)) {
            return;
        }

        $geolocation = Util::geolocation(request()->ip());

        if (is_array($geolocation) && $geolocation['geoplugin_status'] === 404) {
            return;
        }

        $this->setPropsValues(
            $user,
            $geolocation,
            [
                'country'         => 'geoplugin_countryName',
                'country_code'    => 'geoplugin_countryCode',
                'state'           => 'geoplugin_regionName',
                'state_code'      => 'geoplugin_regionCode',
                'city'            => 'geoplugin_city',
                'latitude'        => 'geoplugin_latitude',
                'longitude'       => 'geoplugin_longitude',
                'currency_code'   => 'geoplugin_currencyCode',
                'currency_symbol' => 'geoplugin_currencySymbol_UTF8',
                'timezone'        => 'geoplugin_timezone',
                'ip'              => 'geoplugin_request',
            ]
        );
    }

    /**
     * Set geolocation properties.
     *
     * @param User $user
     * @param array $props Model To Geoplugin Map array.
     * @return void
     */
    private function setPropsValues(User $user, $geolocation, array $props)
    {
        foreach ($props as $propKey => $propValue) {
            if (!empty($geolocation[$propValue]) && empty($user->{$propKey})) {
                $user->{$propKey} = $geolocation[$propValue];
            }
        }

        $user->save();
    }
}
