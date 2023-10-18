<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Facades\App\Services\Util;

class FormSetting extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'email_notifications',
        'accept_responses',
        'form_id',
        'domains',
        'steps_summary',
        'enable_thankyou_url',
        'thankyou_message',
        'thankyou_url',
        'response_limit',
        'enable_google_recaptcha',
        'google_recaptcha_key_id',
        'geolocation',
        'submit_action',
        'post_data_to_url',
        'append_data_to_url',
        'tracking_ga4_property',
        'footer_text',
        'all_steps_footer',
        'trim_trailing_zeros',
    ];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function domainsArray()
    {
        return explode(',', $this->domains);
    }

    public function addDomain($domain)
    {
        if (empty($this->domains)) {
            $domains = [];
        } else {
            $domains = $this->domainsArray();
        }
        if (!in_array($domain, $domains)) {
            array_push($domains, $domain);
            $this->domains = implode(',', $domains);
            $this->save();
        }
    }

    public function googleRecaptchaKey()
    {
        return $this->belongsTo('App\GoogleRecaptchaKey');
    }

    public function setGeolocationAttribute($value)
    {
        if (Util::isJson($value)) {
            $this->attributes['geolocation'] = $value;

            return;
        }

        $this->attributes['geolocation'] = empty($value) ? null : json_encode($value);
    }

    public function getGeolocationAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        $geolocation = json_decode($value, true);

        foreach ($geolocation as &$item) {
            $item['allow'] = (int) $item['allow'];
        }

        return $geolocation;
    }

    /**
     * @param int $formId
     * @param string|null $footerText
     * @param string $allStepsFooter
     * @return FormSetting|null
     */
    public function updateFooterText(int $formId, ?string $footerText, string $allStepsFooter): ?FormSetting
    {
        return $this->updateOrCreate([
            'form_id' =>  $formId,
        ], [
            'footer_text' => $footerText,
            'all_steps_footer' => (int)filter_var($allStepsFooter, FILTER_VALIDATE_BOOLEAN)
        ]);
    }
}
