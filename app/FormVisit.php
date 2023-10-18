<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Facades\App\Services\Util;
use Illuminate\Http\Request;
use App\Form;
use App\FormVariant;
use App\Visitor;
use Agent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormVisit extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'ip',
        'form_id',
        'os',
        'device_type',
        'browser',
        'source_url',
        'visitor_id',
        'user_agent',
        'form_experiment_id',
        'device_name',
        'is_robot',
        'robot_name',
        'form_variant_id',
        'geolocation_forbidden',
        'country',
        'country_code',
        'state',
        'state_code',
        'city',
        'latitude',
        'longitude',
        'currency_code',
        'currency_symbol',
        'timezone',
    ];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function totalVisits(array $formIds)
    {
        return $this->whereIn('form_id', $formIds)
                ->sum('times');
    }

    public function visitor()
    {
        return $this->belongsTo('App\Visitor');
    }

    public function formLead()
    {
        return $this->hasOne('App\FormLead');
    }

    public function createFromParams(
        Request $request,
        Form $form,
        FormVariant $formVariant,
        Visitor $visitor,
        array $geolocation,
        $previewMode = false
    ) {

        $experimentId = $previewMode ? null : $form->current_experiment_id;

        $source_url = $request->filled('source_url')
        ? $request->input('source_url')
        : $request->headers->get('referer');

        $formVisit = [
            'form_id'            => $form->id,
            'visitor_id'         => $visitor->id,
            'form_experiment_id' => $experimentId,
            'form_variant_id'    => $formVariant->id,
            'os'                 => Agent::platform(),
            'device_type'        => Util::deviceType(),
            'device_name'        => Agent::device(),
            'robot'              => Agent::robot(),
            'is_robot'           => Agent::isRobot(),
            'browser'            => Agent::browser(),
            'source_url'         => $source_url,
            'ip'                 => $request->ip(),
            'user_agent'         => $request->headers->get('User-Agent'),
        ];

        if ($geolocation['geoplugin_status'] === 404) {
            return $this->create($formVisit);
        }

        $geolocationInfo = [
            'country'         => $geolocation['geoplugin_countryName'],
            'country_code'    => $geolocation['geoplugin_countryCode'],
            'state'           => $geolocation['geoplugin_regionName'],
            'state_code'      => $geolocation['geoplugin_regionCode'],
            'city'            => $geolocation['geoplugin_city'],
            'latitude'        => $geolocation['geoplugin_latitude'],
            'longitude'       => $geolocation['geoplugin_longitude'],
            'currency_code'   => $geolocation['geoplugin_currencyCode'],
            'currency_symbol' => $geolocation['geoplugin_currencySymbol_UTF8'],
            'timezone'        => $geolocation['geoplugin_timezone'],
        ];

        $formVisit = array_merge($formVisit, $geolocationInfo);

        return $this->create($formVisit);
    }

    /**
     *
     * @param string|null $url .
     * @return string|null
     */
    public function getSourceUrlAttribute(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        $baseUrl = strtok($url, '?');
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['query'])) {
            $query = $parsedUrl['query'];
            parse_str($query, $parameters);
            unset($parameters['token']);
            $newQuery = http_build_query($parameters);
            $newUrl = $baseUrl . '?' . $newQuery;
            return $newUrl;
        }

        return $url;
    }
}
