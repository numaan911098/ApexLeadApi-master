<?php

namespace App;

use App\Models\Form\FormTrackingEvent;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Facades\App\Services\Util;
use App\Enums\FormVariantTypesEnum;
use App\Enums\FormSubmitActionEnum;
use App\Enums\SpecialDatesEnum;
use App\Enums\TimePeriodsEnum;
use App\FormSetting;
use App\FormLead;
use App\Models\FormLeadView;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\User;
use App\FormEmailNotification;
use App\Models\FormPartialLead;
use App\Models\ContactState;
use App\FormWebhook;
use App\Models\GlobalPartialLeadSetting;
use Illuminate\Support\Facades\DB;
use Request;
use Auth;
use App\Enums\FormConnectionsEnum;
use App\Enums\Form\FormTrackingEventTypesEnum;

class Form extends Model
{
    use SoftDeletes;
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'key',
        'title',
        'created_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function formSteps()
    {
        return $this->hasMany('App\FormStep');
    }

    public function formVisits()
    {
        return $this->hasMany('App\FormVisit');
    }

    public function formTrackingEvents(): HasMany
    {
        return $this->hasMany(FormTrackingEvent::class);
    }

    /**
     * Define the "formLeads" relationship for leads only.
     *
     * @return HasMany
     */
    public function formLeads(): HasMany
    {
        return $this->hasMany(FormLead::class)->where('is_partial', false);
    }

    /**
     * Define the "formLeadsPartial" relationship for partial leads.
     *
     * @return HasMany
     */
    public function formLeadsPartial(): HasMany
    {
        return $this->hasMany(FormLead::class)->where('is_partial', true);
    }

    public function viewedLeads()
    {
        return $this->hasManyThrough(FormLeadView::class, FormLead::class, 'form_id', 'lead_id', 'id', 'id');
    }

    public function formTotalLeads()
    {
        return $this->hasMany(FormLead::class)->whereDate('created_at', '>=', SpecialDatesEnum::UNREAD_LEADS_FROM);
    }

    public function variants()
    {
        return $this->hasMany('App\FormVariant');
    }

    public function formVariants()
    {
        return $this->hasMany('App\FormVariant');
    }

    public function currentExperiment()
    {
        return $this->belongsTo('App\FormExperiment');
    }

    public function formExperiments()
    {
        return $this->hasMany('App\FormExperiment');
    }

    public function formWebhooks()
    {
        return $this->hasMany('App\FormWebhook');
    }

    public function formSetting()
    {
        if (!empty($this->id)) {
            $this->hasOne(FormSetting::class)->firstOrCreate([
                'form_id' => $this->id
            ], [
                'email_notifications' => true,
                'accept_responses' => true,
                'domains' => null,
                'steps_summary' => false,
                'enable_thankyou_url' => false,
                'enable_google_recaptcha' => false,
                'thankyou_message' => 'Thank you for submitting your details.',
                'thankyou_url' => null,
                'response_limit' => -1,
                'submit_action' => FormSubmitActionEnum::MESSAGE,
                'post_data_to_url' => false,
                'append_data_to_url' => false,
                'trim_trailing_zeros' => false
            ]);
        }

        return $this->hasOne(FormSetting::class);
    }

    public function formEmailNotification()
    {
        $user = $this->createdBy;
        $this->hasOne('App\FormEmailNotification')->firstOrCreate([
            'form_id' => $this->id
        ], [
            'subject' => 'New Lead captured',
            'to' => $user->email,
            'cc' => null,
            'bcc' => null,
            'from_name' => 'LeadGen App',
            'reply_to' => true,
        ]);

        return $this->hasOne('App\FormEmailNotification');
    }

    /**
     * Add default values to partial leads table
     *@return HasOne
     */
    public function formPartialLeads(): HasOne
    {
        $user = $this->createdBy;
        $globalSettings = GlobalPartialLeadSetting::where('user_id', $user->id)->first();

        $this->hasOne(FormPartialLead::class)->firstOrCreate([
            'form_id' => $this->id
        ], [
            'enabled' => $globalSettings->enabled ? $globalSettings->enabled : false,
            'consent_type' => $globalSettings->enabled ? $globalSettings->consent_type : 'informed'
        ]);

        return $this->hasOne(FormPartialLead::class);
    }

    public function formSettings()
    {
        return array_merge(
            $this->formSetting->toArray(),
            $this->formPartialLeads->toArray(),
            Arr::only(
                $this->formEmailNotification->toArray(),
                ['subject', 'to', 'cc', 'bcc', 'from_name', 'reply_to']
            )
        );
    }

    public function getConnections($forms)
    {
        $connections = [];
        foreach ($forms as $formItem) {
            $webhooks = FormWebhook::where('form_id', $formItem->id)->with('formVariant')->latest()->get();
            foreach ($webhooks as $webhook) {
                $connections[] = [
                    'type' => FormConnectionsEnum::WEBHOOK,
                    'data' => $webhook->toArray(),
                    'form_id' => $webhook->form_id,
                    'form_title' => Form::where('id', $webhook->form_id)->value('title'),
                    'form_variant_id' => $webhook->form_variant_id,
                    'form_webhook_requests_count' => FormWebhookRequest::where('form_webhook_id', $webhook->id)->count()
                ];
            }
            $formContactStates = ContactState::where('form_id', $formItem->id)->get();
            foreach ($formContactStates as $formContactState) {
                $formContactState->type = FormConnectionsEnum::CONTACTSTATE;
                $connections[] = [
                    'type' => FormConnectionsEnum::CONTACTSTATE,
                    'data' => $formContactState->toArray(),
                    'form_id' =>  $formContactState->form_id,
                    'form_title' => Form::where('id', $formContactState->form_id)->value('title'),
                    'form_variant_id' =>  $formContactState->form_variant_id,
                    'secret_key' =>  $formContactState->secret_key
                ];
            }
            $formTrackingEvents = FormTrackingEvent::where('form_id', $formItem->id)
            ->where('event', FormTrackingEventTypesEnum::TRUSTEDFORM)
            ->where('configured', 1)
            ->get();
            foreach ($formTrackingEvents as $formTrackingEvent) {
                $formTrackingEvent->type = FormConnectionsEnum::TRUSTEDFORM;
                $connections[] = [
                    'type' => FormConnectionsEnum::TRUSTEDFORM,
                    'data' => $formTrackingEvent->toArray(),
                    'form_id' =>  $formTrackingEvent->form_id,
                    'form_title' => Form::where('id', $formTrackingEvent->form_id)->value('title')
                ];
            }
        }
        return $connections;
    }

    public function integration()
    {
        $contactstate = ContactState::where('form_id', $this->id)
            ->first();
        if ($contactstate) {
            return $contactstate->toArray();
        }
    }

    public function championVariant(array $with = [])
    {
        return FormVariant::with($with)->whereHas(
            'formVariantType',
            fn (Builder $query) => $query->where('type', FormVariantTypesEnum::CHAMPION)
        )
            ->where('form_id', $this->id)
            ->first();
    }

    public function conversionCount()
    {
        $subQuery1 = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->where('form_leads.is_partial', false)
            ->where('form_leads.form_id', $this->id)
            ->whereNull('form_leads.deleted_at')
            ->select('form_visits.visitor_id', 'form_leads.form_id')
            ->groupBy('form_visits.visitor_id', 'form_leads.form_id');

        $subQuery2 = DB::table(DB::raw("({$subQuery1->toSql()}) as leads1"))
            ->mergeBindings($subQuery1)
            ->select('leads1.form_id')
            ->groupBy('leads1.visitor_id');

        $query = DB::table(DB::raw("({$subQuery2->toSql()}) as leads2"))
            ->mergeBindings($subQuery2)
            ->select(
                'leads2.form_id',
                DB::raw('COUNT(*) as conversions')
            );

        return $query->get()->first()->conversions;
    }

    public function visitorCount()
    {
        $subQuery = DB::table('form_visits')
            ->select('form_visits.visitor_id')
            ->where('form_visits.form_id', $this->id)
            ->groupBy('form_visits.visitor_id');

        $query = DB::table(DB::raw("({$subQuery->toSql()}) as form_visits_1"))
            ->mergeBindings($subQuery)
            ->select(DB::raw('COUNT(*) as visitors'));

        return $query->get()->first()->visitors;
    }

    public function leadsCount()
    {
        return $this->formLeads()->count();
    }

    public function conversionRate()
    {
        $visitorCount = $this->visitorCount();

        if ($visitorCount > 0) {
            $conversionRate = ($this->conversionCount() / $visitorCount) * 100;
            return round($conversionRate, 2);
        }
        return 0;
    }

    public function duplicateWithVariantsAndSettings($resetEmailSetting = false, $resetThankyou = false)
    {
        try {
            DB::beginTransaction();

            $duplicateForm = $this->replicate();
            $duplicateForm->title = $this->title . ' Copy';
            $duplicateForm->key = Util::uuid4();
            $duplicateForm->current_experiment_id = null;
            $duplicateForm->save();

            foreach ($this->variants as $variant) {
                $duplicateVariant = $variant->replicate();
                $duplicateVariant->form_variant_type_id = $variant->formVariantType->id;
                $duplicateVariant->form_id = $duplicateForm->id;
                $duplicateVariant->save();

                foreach ($variant->formSteps as $formStep) {
                    $step = $formStep->replicate();
                    $step->form_id = $duplicateForm->id;
                    $step->form_variant_id = $duplicateVariant->id;
                    $step->save();

                    foreach ($formStep->formQuestions as $formQuestion) {
                        $question = $formQuestion->replicate();
                        $question->form_step_id = $step->id;
                        $question->save();
                    }

                    foreach ($formStep->formElements as $formElement) {
                        $element = $formElement->replicate();
                        $element->form_step_id = $step->id;
                        $element->save();
                    }
                }

                foreach ($variant->formHiddenFields as $formHiddenField) {
                    $hiddenField = $formHiddenField->replicate();
                    $hiddenField->form_id = $duplicateForm->id;
                    $hiddenField->form_variant_id = $duplicateVariant->id;
                    $hiddenField->save();
                }

                // variant specific webhook
                foreach ($variant->webhooks as $formWebhook) {
                    $webhook = $formWebhook->replicate();
                    $webhook->form_id = $duplicateForm->id;
                    $webhook->form_variant_id = $duplicateVariant->id;
                    $webhook->save();
                }

                $variantTheme = $variant->formVariantTheme->replicate();
                $variantTheme->form_variant_id = $duplicateVariant->id;
                $variantTheme->save();
            }

            // global webhook
            foreach ($this->formWebhooks as $formWebhook) {
                $webhook = $formWebhook->replicate();
                $webhook->form_id = $duplicateForm->id;
                $webhook->form_variant_id = null;
                $webhook->save();
            }

            $duplicateFormSetting = $this->formSetting->replicate();

            if ($resetThankyou) {
                $duplicateFormSetting->thankyou_message = 'Thank you for submitting your details.';
                $duplicateFormSetting->thankyou_url = null;
                $duplicateFormSetting->submit_action = 'MESSAGE';
            }

            $duplicateFormSetting->form_id = $duplicateForm->id;

            if (!$resetEmailSetting) {
                $duplicateFormEmailNotification = $this->formEmailNotification->replicate();
            } else {
                $duplicateFormSetting->email_notifications = 0;
                $duplicateFormEmailNotification = new FormEmailNotification([
                    'subject' => null,
                    'to' => null,
                    'cc' => null,
                    'bcc' => null,
                    'from_name' => null,
                ]);
            }

            $duplicateFormEmailNotification->form_id = $duplicateForm->id;
            $duplicateFormSetting->save();
            $duplicateFormEmailNotification->save();

            DB::commit();

            return $duplicateForm;
        } catch (\Exception $e) {
            DB::rollback();

            Util::logException($e);
        }

        return false;
    }

    public function duplicate()
    {
        return $this->duplicateWithVariantsAndSettings(true, true);
    }

    public function getRecaptchaSiteKey()
    {
        $requestDomain = parse_url(Request::header('referer'), PHP_URL_HOST);
        $formsDomain = parse_url(Util::config('leadgen.forms_domain'), PHP_URL_HOST);

        if ($requestDomain === $formsDomain) {
            return Util::config('leadgen.google_irecaptcha_site_key_forms_domain');
        }

        $this->formSetting = $this->formSetting;

        if (!$this->formSetting->enable_google_recaptcha) {
            return '';
        }

        if ($this->formSetting->google_recaptcha_key_id) {
            $this->formSetting->load('googleRecaptchaKey');

            return $this->formSetting->googleRecaptchaKey->site_key;
        }

        return Util::config('leadgen.google_irecaptcha_site_key');
    }

    /**
     * Check if form is currently blocked.
     *
     * @param User $user
     * @return boolean
     */
    public function isBlocked(User $user = null)
    {
        if (empty($user)) {
            $user = Auth::user();
        }

        if ($user->isAdmin() || $user->isSuperCustomer()) {
            return false;
        }

        $plan = $user->plan();

        if ($user->isOneToolUser() && $user->hasActiveOneToolSubscription()) {
            $formIds = $user->forms()
                ->latest()
                ->take($plan->form_limit)
                ->get()
                ->pluck(['id'])
                ->toArray();

            return !in_array($this->id, $formIds, true);
        }

        if ((!$user->isPastDue()) && ($user->hasActiveSubscription($plan) || $plan->in_trial)) {
            $formIds = $user->forms()
                ->latest()
                ->take($plan->form_limit)
                ->get()
                ->pluck(['id'])
                ->toArray();
            return !in_array($this->id, $formIds, true);
        }

        $formIds = $user->forms()
            ->latest()
            ->take($plan->form_base_limit)
            ->get()
            ->pluck(['id'])
            ->toArray();
        return !in_array($this->id, $formIds, true);
    }

    public function isBlockedByGeolocation(array $ipInfo)
    {
        $geolocation = $this->formSetting->geolocation;

        if (empty($geolocation)) {
            return true;
        }

        try {
            if (!empty($ipInfo) && $ipInfo['geoplugin_status'] === 404) {
                return true;
            }

            $othersAllow = true;

            foreach ($geolocation as $item) {
                if ($item['country'] === $ipInfo['geoplugin_countryName']) {
                    return $item['allow'];
                }

                if (empty($item['country'])) {
                    $othersAllow = $item['allow'];
                }
            }

            return $othersAllow;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Show branding on form generator.
     *
     * @return boolean
     */
    public function showBranding()
    {
        $user = $this->createdBy;

        if ($user->isSuperCustomer() || $user->isAdmin()) {
            return false;
        }

        if ($user->plan()->isFreeTrialPlan()) {
            return false;
        }

        return !$user->hasActiveSubscription($user->plan());
    }

    /**
     * Handle branding on published
     *
     * @return boolean
     */
    public function brandingOnPublished()
    {
        $user = $this->createdBy;

        if ($user->isSuperCustomer() || $user->isAdmin()) {
            return false;
        }

        if (($user->hasActiveSubscription($user->plan())) && ($user->plan()->isProTypePlan())) {
            return true;
        }

        if ($user->plan()->isFreeTrialPlan()) {
            return true;
        }

        return !$user->hasActiveSubscription($user->plan());
    }

    /**
     * Form views/visits query.
     *
     * @static
     * @access public
     *
     * @param array   $formIds Array of forms ids.
     * @param string  $startDate Start date e.g. 2019-3-24.
     * @param string  $endDate End date e.g. 2019-3-30.
     * @param boolean $count Get views count.
     * @param string  $countBy Count by days|hours.
     * @return array
     */
    public static function formVisitsQuery(
        array $formIds,
        $startDate,
        $endDate,
        $count = true,
        $countBy = TimePeriodsEnum::DAYS
    ) {
        $createDateFormat = ($countBy === TimePeriodsEnum::DAYS) ? '%Y-%m-%d' : '%Y-%m-%d %H:00:00';
        $createDateFormat = DB::raw('DATE_FORMAT(form_visits.created_at, "' . $createDateFormat . '")
        as visits_created_at');

        $query = DB::table('form_visits')->whereIn('form_visits.form_id', $formIds);

        if (!empty($startDate) && !empty($endDate)) {
            $query = $query->whereBetween(
                'form_visits.created_at',
                [
                    $startDate->toDateString(),
                    $endDate->copy()->addDay()->toDateString()
                ]
            );
        }

        if ($count) {
            $visits = $query
                ->select($createDateFormat, DB::raw('COUNT(*) as visits'))
                ->groupBy('visits_created_at')
                ->orderBy('visits_created_at')
                ->get();

            if ($visits->isEmpty()) {
                return collect([]);
            }

            if (empty($startDate) || empty($endDate)) {
                $startDate = new Carbon($visits->first()->visits_created_at);
                $endDate = new Carbon($visits->last()->visits_created_at);
            }

            if ($countBy === TimePeriodsEnum::DAYS) {
                self::fillDays($visits, $startDate->copy(), $endDate->copy(), [
                    'date' => 'visits_created_at',
                    'count' => 'visits',
                ]);
            } elseif ($countBy === TimePeriodsEnum::HOURS) {
                self::fillHours($visits, $startDate->copy(), $endDate->copy(), [
                    'date' => 'visits_created_at',
                    'count' => 'visits',
                ]);
            }
        } else {
            $visits = $query->get();
        }

        return $visits;
    }

    /**
     * Form conversions query.
     *
     * @static
     * @access public
     *
     * @param array   $formIds Array of forms ids.
     * @param string  $startDate Start date e.g. 2019-3-24.
     * @param string  $endDate End date e.g. 2019-3-30.
     * @param boolean $count Get views count.
     * @param string  $countBy Count by days|hours.
     * @return array
     */
    public static function formConversionsQuery(
        array $formIds,
        $startDate,
        $endDate,
        $count = true,
        $countBy = TimePeriodsEnum::DAYS
    ) {
        $createDateFormat = ($countBy === TimePeriodsEnum::DAYS) ? '%Y-%m-%d' : '%Y-%m-%d %H:00:00';
        $createDateFormat = DB::raw('DATE_FORMAT(form_leads.created_at, "' . $createDateFormat . '")
        as lead_created_at');

        $subQuery1 = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->whereIn('form_leads.form_id', $formIds)
            ->where('form_leads.is_partial', false)
            ->whereNull('form_leads.deleted_at')
            ->select('form_visits.visitor_id', 'form_leads.*', $createDateFormat)
            ->groupBy('form_visits.visitor_id', 'form_leads.form_id')
            ->orderBy('lead_created_at');

        if (!empty($startDate) && !empty($endDate)) {
            $subQuery1 = $subQuery1->whereBetween('form_leads.created_at', [
                $startDate->toDateString(),
                $endDate->copy()->addDay()->toDateString(),
            ]);
        }

        $subQuery2 = DB::table(DB::raw("({$subQuery1->toSql()}) as leads1"))
            ->mergeBindings($subQuery1)
            ->select('leads1.form_id', 'leads1.lead_created_at')
            ->groupBy('leads1.visitor_id');
        if ($count) {
            $query = DB::table(DB::raw("({$subQuery2->toSql()}) as leads2"))
                ->mergeBindings($subQuery2)
                ->select(
                    'leads2.form_id',
                    'leads2.lead_created_at as conversions_created_at',
                    DB::raw('COUNT(*) as conversions')
                )
                ->groupBy('lead_created_at');

            $conversions = $query->get();

            if ($conversions->isEmpty()) {
                return collect([]);
            }

            if (empty($startDate) || empty($endDate)) {
                $startDate = new Carbon($conversions->first()->conversions_created_at);
                $endDate = new Carbon($conversions->last()->conversions_created_at);
            }

            if ($countBy === TimePeriodsEnum::DAYS) {
                self::fillDays($conversions, $startDate->copy(), $endDate->copy(), [
                    'date' => 'conversions_created_at',
                    'count' => 'conversions',
                ]);
            } elseif ($countBy === TimePeriodsEnum::HOURS) {
                self::fillHours($conversions, $startDate->copy(), $endDate->copy(), [
                    'date' => 'conversions_created_at',
                    'count' => 'conversions',
                ]);
            }
        } else {
            $conversions = $subQuery1->get();
        }

        return $conversions;
    }

    /**
     * Form leads query.
     *
     * @static
     * @access public
     *
     * @param array   $formIds Array of forms ids.
     * @param string  $startDate Start date e.g. 2019-3-24.
     * @param string  $endDate End date e.g. 2019-3-30.
     * @param boolean $count Get views count.
     * @param string  $countBy Count by days|hours.
     * @return array
     */
    public static function formLeadsQuery(
        array $formIds,
        $startDate,
        $endDate,
        $count = true,
        $countBy = TimePeriodsEnum::DAYS
    ) {
        $createDateFormat = ($countBy === TimePeriodsEnum::DAYS) ? '%Y-%m-%d' : '%Y-%m-%d %H:00:00';
        $createDateFormat = DB::raw('DATE_FORMAT(form_leads.created_at, "' . $createDateFormat . '")
        as leads_created_at');

        $query = DB::table('form_leads')
            ->whereNull('form_leads.deleted_at')
            ->where('form_leads.is_partial', '=', false)
            ->whereIn('form_leads.form_id', $formIds);

        if (!empty($startDate) && !empty($endDate)) {
            $query = $query->whereBetween(
                'form_leads.created_at',
                [
                    $startDate->toDateString(),
                    $endDate->copy()->addDay()->toDateString()
                ]
            );
        }

        if ($count) {
            $leads = $query
                ->select($createDateFormat, DB::raw('COUNT(*) as leads'))
                ->groupBy('leads_created_at')
                ->get();


            if ($leads->isEmpty()) {
                return collect([]);
            }

            if (empty($startDate) || empty($endDate)) {
                $startDate = new Carbon($leads->first()->leads_created_at);
                $endDate = new Carbon($leads->last()->leads_created_at);
            }

            if ($countBy === TimePeriodsEnum::DAYS) {
                self::fillDays($leads, $startDate->copy(), $endDate->copy(), [
                    'date' => 'leads_created_at',
                    'count' => 'leads',
                ]);
            } elseif ($countBy === TimePeriodsEnum::HOURS) {
                self::fillHours($leads, $startDate->copy(), $endDate->copy(), [
                    'date' => 'leads_created_at',
                    'count' => 'leads',
                ]);
            }
        } else {
            $leads = $query->get();
        }

        return $leads;
    }

    /**
     * Form  Partial leads query.
     *
     * @static
     * @access public
     *
     * @param array   $formIds Array of forms ids.
     * @param string  $startDate Start date e.g. 2019-3-24.
     * @param string  $endDate End date e.g. 2019-3-30.
     * @param boolean $count Get views count.
     * @param string  $countBy Count by days|hours.
     * @return array
     */
    public static function formPartialLeadsQuery(
        array $formIds,
        $startDate,
        $endDate,
        bool $count = true,
        string $countBy = TimePeriodsEnum::DAYS
    ) {
        $createDateFormat = ($countBy === TimePeriodsEnum::DAYS) ? '%Y-%m-%d' : '%Y-%m-%d %H:00:00';
        $createDateFormat = DB::raw('DATE_FORMAT(form_leads.created_at, "' . $createDateFormat . '")
        as leads_created_at');

        $query = DB::table('form_leads')
            ->where('form_leads.is_partial', '=', true)
            ->whereNull('form_leads.deleted_at')
            ->whereIn('form_leads.form_id', $formIds);

        if (!empty($startDate) && !empty($endDate)) {
            $query = $query->whereBetween(
                'form_leads.created_at',
                [
                    $startDate->toDateString(),
                    $endDate->copy()->addDay()->toDateString()
                ]
            );
        }

        if ($count) {
            $leads = $query
                ->select($createDateFormat, DB::raw('COUNT(*) as leads'))
                ->groupBy('leads_created_at')
                ->get();

            if ($leads->isEmpty()) {
                return collect([]);
            }

            if (empty($startDate) || empty($endDate)) {
                $startDate = new Carbon($leads->first()->leads_created_at);
                $endDate = new Carbon($leads->last()->leads_created_at);
            }

            if ($countBy === TimePeriodsEnum::DAYS) {
                self::fillDays($leads, $startDate->copy(), $endDate->copy(), [
                    'date' => 'leads_created_at',
                    'count' => 'leads',
                ]);
            } elseif ($countBy === TimePeriodsEnum::HOURS) {
                self::fillHours($leads, $startDate->copy(), $endDate->copy(), [
                    'date' => 'leads_created_at',
                    'count' => 'leads',
                ]);
            }
        } else {
            $leads = $query->get();
        }

        return $leads;
    }

    /**
     * Form visitors query.
     *
     * @static
     * @access public
     *
     * @param array   $formIds Array of forms ids.
     * @param string  $startDate Start date e.g. 2019-3-24.
     * @param string  $endDate End date e.g. 2019-3-30.
     * @param boolean $count Get views count.
     * @param string  $countBy Count by days|hours.
     * @return array
     */
    public static function formVisitorsQuery(
        array $formIds,
        $startDate,
        $endDate,
        $count = true,
        $countBy = TimePeriodsEnum::DAYS
    ) {
        $createDateFormat = ($countBy === TimePeriodsEnum::DAYS) ? '%Y-%m-%d' : '%Y-%m-%d %H:00:00';
        $createDateFormat = DB::raw('DATE_FORMAT(form_visits.created_at, "' . $createDateFormat . '")
        as visitors_created_at');

        $subQuery1 = DB::table('form_visits')
            ->select('form_visits.visitor_id', $createDateFormat)
            ->whereIn('form_visits.form_id', $formIds)
            ->groupBy('form_visits.visitor_id')
            ->orderBy('visitors_created_at');

        if (!empty($startDate) && !empty($endDate)) {
            $subQuery1 = $subQuery1->whereBetween(
                'form_visits.created_at',
                [
                    $startDate->toDateString(),
                    $endDate->copy()->addDay()->toDateString()
                ]
            );
        }

        if ($count) {
            $query = DB::table(DB::raw("({$subQuery1->toSql()}) as form_visits_1"))
                ->mergeBindings($subQuery1)
                ->select('form_visits_1.visitors_created_at', DB::raw('COUNT(*) as visitors'))
                ->groupBy('form_visits_1.visitors_created_at');
            $visitors = $query->get();

            if ($visitors->isEmpty()) {
                return collect([]);
            }

            if (empty($startDate) || empty($endDate)) {
                $startDate = new Carbon($visitors->first()->visitors_created_at);
                $endDate = new Carbon($visitors->last()->visitors_created_at);
            }

            if ($countBy === TimePeriodsEnum::DAYS) {
                self::fillDays($visitors, $startDate->copy(), $endDate->copy(), [
                    'date' => 'visitors_created_at',
                    'count' => 'visitors',
                ]);
            } elseif ($countBy === TimePeriodsEnum::HOURS) {
                self::fillHours($visitors, $startDate->copy(), $endDate->copy(), [
                    'date' => 'visitors_created_at',
                    'count' => 'visitors',
                ]);
            }
        } else {
            $visitors = $subQuery1->get();
        }

        return $visitors;
    }

    /**
     * Form completion time query.
     *
     * @static
     * @access public
     *
     * @param array   $formIds Array of forms ids.
     * @param string  $startDate Start date e.g. 2019-3-24.
     * @param string  $endDate End date e.g. 2019-3-30.
     * @return array
     */
    public static function formAverageCompletionQuery(array $formIds, $startDate, $endDate)
    {
        $query = DB::table('form_visits')
            ->join('form_leads', 'form_visits.id', '=', 'form_leads.form_visit_id')
            ->whereIn('form_visits.form_id', $formIds)
            ->select(
                DB::raw(
                    'AVG(
                        TIMESTAMPDIFF(
                            SECOND,
                            IFNULL(
                                form_visits.interacted_at,
                                form_visits.created_at
                            ),
                            form_leads.created_at
                        )
                    ) / 60 as value'
                )
            );

        if (!empty($startDate) && !empty($endDate)) {
            $query = $query->whereBetween(
                'form_visits.created_at',
                [
                    $startDate->toDateString(),
                    $endDate->copy()->addDay()->toDateString()
                ]
            );
        }

        $completionTime = $query->get()->first();

        return round($completionTime->value, 2);
    }

    /**
     * Fill missing days in the result set.
     *
     * @param Collection $resultSet Collection of items.
     * @param Carbon $startDate Start date.
     * @param Carbon $endDate End date.
     * @param array $fields Fields.
     * @return void
     */
    private static function fillDays(
        Collection &$resultSet,
        Carbon $startDate,
        Carbon $endDate,
        array $fields = []
    ) {
        $startIndex = 0;
        while ($startDate->lessThan($endDate)) {
            $hasDate = $resultSet
                ->where(
                    $fields['date'],
                    $startDate->format('Y-m-d')
                )
                ->count() > 0;

            if (!$hasDate) {
                $resultSet->splice($startIndex, 0, [(object) [
                    $fields['date'] => $startDate->format('Y-m-d'),
                    $fields['count'] => 0,
                ]]);
            }
            $startDate->day = $startDate->day + 1;
            $startIndex++;
        }
    }

    /**
     * Fill missing hours in the result set.
     *
     * @param Collection $resultSet Collection of items.
     * @param Carbon $startDate Start date.
     * @param Carbon $endDate End date.
     * @param array $fields Fields.
     * @return void
     */
    private static function fillHours(
        Collection &$resultSet,
        Carbon $startDate,
        Carbon $endDate,
        array $fields = []
    ) {
        $startIndex = 0;
        while ($startDate->lessThan($endDate)) {
            $hasDate = $resultSet
                ->where(
                    $fields['date'],
                    $startDate->format('Y-m-d H:00:00')
                )
                ->count() > 0;

            if (!$hasDate) {
                $resultSet->splice($startIndex, 0, [(object) [
                    $fields['date'] => $startDate->format('Y-m-d H:00:00'),
                    $fields['count'] => 0,
                ]]);
            }
            $startDate->hour = $startDate->hour + 1;
            $startIndex++;
        }
    }
}
