<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use App\Enums\SignupSourcesEnum;
use App\Enums\SubscriptionsEnum;
use App\Enums\PlansEnum;
use App\Enums\StripePlansEnum;
use App\Enums\OneToolPlansEnum;
use App\Enums\OperatorsEnum;
use App\Enums\PackageBuilder\FeatureEnum;
use App\Enums\PackageBuilder\FeaturePropertyEnum;
use App\Enums\RolesEnum;
use App\Enums\Paddle\PaddleSubscriptionStatusEnum;
use App\Enums\Subscription\SubscriptionTypesEnum;
use App\Enums\TimePeriodsEnum;
use App\Plan;
use App\PaddleSubscription;
use App\Models\Credential;
use App\Models\GlobalPartialLeadSetting;
use App\Models\GoogleUser;
use App\Models\InactiveUser;
use App\Models\LoginHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Newsletter;
use App\Models\TwoFactorSetting;
use App\Models\UserSettings;
use App\Models\UserLimitation;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use Billable;
    use SoftDeletes;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_token',
        'agree_terms',
        'subscribe_newsletter',
        'source',
        'active',
        'signup_params',
        'default_plan_id',
        'sign_up_plan_id',
        'ip',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'stripe_id',
        'card_brand',
        'card_last_four',
        'trial_ends_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected const DEFAULT_FREQUENCY = 'for_every_new_lead';
    protected $plan_expired = false;

    public function getFirstNameAttribute()
    {
        $name = explode(' ', $this->name);

        return array_shift($name);
    }

    public function getLastNameAttribute()
    {
        $name = explode(' ', $this->name);

        array_shift($name);

        return empty($name) ? '' : implode(' ', $name);
    }

    public function forms()
    {
        return $this->hasMany('App\Form', 'created_by');
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'created_by');
    }

    public function userLeads()
    {
        return $this->hasManyThrough('App\FormLead', 'App\Form', 'created_by', 'form_id', 'id', 'id');
    }

    public function landingPages()
    {
        return $this->hasMany('App\LandingPage', 'created_by');
    }

    public function whitelabel()
    {
        $this->hasOne('App\WhiteLabel')->firstOrCreate([
            'user_id' => $this->id
        ], [
            'enabled' => false,
        ]);

        return $this->hasOne('App\WhiteLabel');
    }

    /**
     * Add default values to Global Partial lead Settings table
     * @return HasOne
     */
    public function globalPartialLeadSetting(): HasOne
    {
        $this->hasOne(GlobalPartialLeadSetting::class)->firstOrCreate([
            'user_id' => $this->id
        ], [
            'enabled' => false,
            'consent_type' => 'informed'
        ]);

        return $this->hasOne(GlobalPartialLeadSetting::class);
    }

    /**
     * Add default values to user_limitations table
     * @return HasOne
     */
    public function userLimitation(): HasOne
    {
        $this->hasOne(UserLimitation::class)->firstOrCreate([
            'user_id' => $this->id
        ], [
            'leadLimitReached' => false
        ]);

        return $this->hasOne(UserLimitation::class);
    }

    public function leadNotificationSetting()
    {
        $this->hasOne('App\Models\LeadNotificationSetting')->firstOrCreate([
            'user_id' => $this->id
        ], [
            'enabled' => true,
            'notification_frequency' => User::DEFAULT_FREQUENCY,
        ]);

        return $this->hasOne('App\Models\LeadNotificationSetting');
    }

    public function twoFactor(): HasOne
    {
        $this->hasOne(TwoFactorSetting::class)->firstOrCreate([
            'user_id' => $this->id
        ], [
            'enabled' => true,
        ]);

        return $this->hasOne(TwoFactorSetting::class);
    }

    /**
     * Get user login histories.
     */
    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class, 'user_id');
    }

    /**
     * Get user latest login history.
     */
    public function latestLoginHistory()
    {
        return $this->loginHistories()->latest()->first();
    }

    /**
     * Get inactive user.
     */
    public function hasNotLoggedIn(Carbon $timePeriod): bool
    {
        return $this->latestLoginHistory()->attempted_at <= $timePeriod->toDateTimeString();
    }

    /**
     * gwt inactive user details
     *
     * @return bool
     */
    public function isInactiveUser(): HasOne
    {
        return $this->hasOne(InactiveUser::class, 'user_id');
    }

    /**
     * check if user is in past due
     */
    public function ispastDue(): bool
    {
        $subscription = $this->getSubscription();

        if ($subscription instanceof \Laravel\Cashier\Subscription) {
            return $subscription->pastDue();
        }
        if ($subscription instanceof PaddleSubscription) {
            return $subscription->hasPastDueStatus();
        }
        return false;
    }

    /**
     *  google user
     */
    public function googleUser(): HasOne
    {
        return $this->hasOne(GoogleUser::class);
    }

    /**
     * check if user is google user
     */
    public function isGoogleUser(): bool
    {
        return $this->googleUser()->exists();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'leadgen.auth' => [
                'source' => $this->source,
            ]
        ];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->id));
    }

    public function hasOneToolProPlan()
    {
        if (!$this->isOneToolUser()) {
            return false;
        }

        $plan = Plan::where('public_id', OneToolPlansEnum::PRO_LITE)
            ->first();

        return $this->oneToolUser->plan_id === $plan->id;
    }

    public function defaultPlan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'default_plan_id');
    }

    public function signUpPlan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'sign_up_plan_id');
    }

    public function verifyEnabled(): HasOne
    {
        return $this->hasOne(UserSettings::class, 'email_verification_enabled', 'verify_enabled');
    }

    /**
     * Get Plan assigned to the user.
     *
     * @return App\Plan
     */
    public function plan()
    {
        if ($this->isOneToolUser()) {
            $plan = Plan::find($this->oneToolUser->plan_id);
            $plan->in_trial = $this->oneToolUser->in_trial;
            $plan->trial_days_left = null;
            $plan->plan_expired = $this->plan_expired;
            return $plan;
        }

        $subscription = $this->getSubscription();
        if (empty($subscription)) {
            $plan = (is_null($this->defaultPlan)) ? Plan::where('public_id', PlansEnum::FREE)->first()
            : $this->defaultPlan;
            $plan->plan_expired = $this->plan_expired;
            $plan->in_trial = false;
            $plan->trial_days_left = 0;
            if (intval($plan->trial_days) > 0) {
                $userCreatedDays = Carbon::now()->diffInDays(Carbon::parse($this->created_at), true);
                $plan->in_trial = $userCreatedDays <= $plan->trial_days;
                $plan->trial_days_left = $userCreatedDays > $plan->trial_days ?
                0 : $plan->trial_days - $userCreatedDays;
            }
            $plan->plan_features = $plan->planFeatures;
            return $plan;
        }
        if ($subscription instanceof \Laravel\Cashier\Subscription) {
            $plan = Plan::where('stripe_plan_id', $subscription->stripe_plan)->first();
            $subscription->stripe = true;
            $subscription->active = $this->hasActiveSubscription($plan);
            $plan->plan_expired = $this->plan_expired;
            $plan->trial_days_left = Carbon::now()->diffInDays(Carbon::parse($subscription->trial_ends_at), true);
            $plan->in_trial = $subscription->stripe_status === 'trialing' &&
            $subscription->trial_ends_at && intval($plan->trial_days_left) > 0;
            $plan->subscription = $subscription;
            $plan->subscription_id = $subscription->stripe_id;
            $plan->subscription_type = SubscriptionTypesEnum::STRIPE;
            $plan->plan_features = $plan->planFeatures;
            return $plan;
        }
        if ($subscription instanceof PaddleSubscription) {
            $plan = Plan::where('paddle_plan_id', $subscription->paddle_plan)->first();
            $subscription->paddle = true;
            $subscription->active = $this->hasActiveSubscription($plan);
            $plan->plan_expired = $this->plan_expired;
            $plan->trial_days_left = Carbon::now()->diffInDays(Carbon::parse($subscription->ends_at), true);
            $plan->in_trial = $subscription->status === PaddleSubscriptionStatusEnum::TRIALING &&
            $subscription->ends_at && intval($plan->trial_days_left) > 0;
            $plan->subscription = $subscription;
            $plan->subscription_id = $subscription->paddle_id;
            $plan->subscription_type = SubscriptionTypesEnum::PADDLE;
            $plan->plan_features = $plan->planFeatures;
            return $plan;
        }
        $plan = (is_null($this->defaultPlan)) ? Plan::where('public_id', PlansEnum::FREE)->first()
        : $this->defaultPlan;
        $plan->plan_features = $plan->planFeatures;
        return $plan;
    }

    /**
     * Get user newletter subscription.
     */
    public function newsletter()
    {
        return $this->hasOne(Newsletter::class, 'user_id', 'id');
    }

    /**
     * Get Onetool user details.
     */
    public function oneToolUser()
    {
        return $this->hasOne('App\OneToolUser');
    }

    /**
     * Get Onetool user details.
     */
    public function isOneToolUser()
    {
        if ($this->source !== SignupSourcesEnum::ONETOOL) {
            return false;
        }

        return !empty($this->oneToolUser);
    }

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }

    /**
     * Has Only Specified
     *
     * @param [type] $role
     * @return boolean
     */
    public function hasRoleOnly(string $role): bool
    {
        if ($this->roles->count() > 1) {
            return false;
        }

        return !empty($this->roles->where('name', $role)->first());
    }

    /**
     * Get Onboarding.
     */
    public function onboarding()
    {
        return $this->hasOne('App\Onboarding');
    }

    public function isAdmin()
    {
        return !empty($this->roles->where('name', RolesEnum::ADMIN)->first());
    }

    public function isCustomer()
    {
        return !empty($this->roles->where('name', RolesEnum::CUSTOMER)->first());
    }

    public function isSuperCustomer()
    {
        return !empty($this->roles->where('name', RolesEnum::SUPER_CUSTOMER)->first());
    }

    /**
     * Get user subscription (Fallback to stripe if there is any subscription).
     */
    public function getSubscription()
    {
        $paddleSubscription = $this->getPaddleSubscription();

        if (!empty($paddleSubscription)) {
            return $paddleSubscription;
        }

        return $this->getStripeSubscription();
    }

    public function getStripeSubscription()
    {
        $plan = Plan::where('public_id', StripePlansEnum::PRO)->first();

        $subscription = $this->subscription(SubscriptionsEnum::MAIN);

        if (empty($subscription)) {
            return null;
        }

        if ($this->hasSubscription($plan) && $subscription->stripe_plan === $plan->stripe_plan_id) {
            return $subscription;
        }

        return null;
    }

    public function getPaddleSubscription(): ?PaddleSubscription
    {
        $plans = Plan::whereNotNull('paddle_plan_id')->get();

        foreach ($plans as $plan) {
            $subscription = PaddleSubscription::orderby('created_at', 'desc')
                ->where('user_id', $this->id)
                ->where('paddle_plan', $plan->paddle_plan_id)
                ->first();

            if ($this->hasSubscription($plan) && $subscription->paddle_plan === $plan->paddle_plan_id) {
                return $subscription;
            }
        }

        return null;
    }

    public function hasSubscription(Plan $plan): bool
    {
        if ($plan->isPaddlePlan()) {
            return $this->hasPaddleSubscription($plan);
        }

        if ($plan->isStripePlan()) {
            if ($this->subscription(SubscriptionsEnum::MAIN)->ended()) {
                return false;
            }

            if ($this->subscription(SubscriptionsEnum::MAIN)->incomplete()) {
                return false;
            }

            return !$this->subscription(SubscriptionsEnum::MAIN)->cancelled();
        }

        return false;
    }

    public function hasActiveSubscription(Plan $plan): bool
    {
        if ($plan->isPaddlePlan()) {
            $subscription = $this->getPaddleSubscription();

            if (empty($subscription)) {
                return false;
            }

            $allowed = [
                PaddleSubscriptionStatusEnum::ACTIVE,
                PaddleSubscriptionStatusEnum::TRIALING,
                PaddleSubscriptionStatusEnum::PAST_DUE,
            ];

            if (in_array($subscription->status, $allowed, true)) {
                return true;
            }

            if ($this->hasPaddleSubscriptionOnGracePeriod($subscription)) {
                return true;
            }
        }

        if ($plan->isStripePlan()) {
            if ($this->subscription(SubscriptionsEnum::MAIN)->ended()) {
                return false;
            }

            if ($this->subscription(SubscriptionsEnum::MAIN)->incomplete()) {
                return false;
            }

            return !$this->subscription(SubscriptionsEnum::MAIN)->cancelled();
        }

        return false;
    }

    public function hasSubscriptionOnGracePeriod(): bool
    {
        $subscription = $this->getSubscription();

        if ($subscription instanceof \Laravel\Cashier\Subscription) {
            return $this->subscription(SubscriptionsEnum::MAIN)->onGracePeriod();
        }

        if ($subscription instanceof PaddleSubscription) {
            return $this->hasPaddleSubscriptionOnGracePeriod($subscription);
        }

        return false;
    }

    public function hasActiveOneToolSubscription(): bool
    {
        if (!$this->isOneToolUser()) {
            return false;
        }

        if ($this->oneToolUser->in_trial) {
            return true;
        }

        return $this->oneToolUser->status === 'active';
    }

    public function hasPaddleSubscription(Plan $plan): bool
    {
        $subscription = PaddleSubscription::orderby('created_at', 'desc')
            ->where('user_id', $this->id)
            ->where('paddle_plan', $plan->paddle_plan_id)
            ->first();

        if (empty($subscription)) {
            return false;
        }

        if ($this->hasPaddleSubscriptionOnGracePeriod($subscription)) {
            return true;
        }

        if (in_array($subscription->status, [PaddleSubscriptionStatusEnum::DELETED], true)) {
            $this->plan_expired = true;
            return false;
        }

        return true;
    }

    public function hasPaddleSubscriptionOnGracePeriod(PaddleSubscription $subscription): bool
    {
        if ($subscription->ends_at && $subscription->ends_at->isFuture()) {
            return true;
        }

        if ($subscription->paused_from && $subscription->paused_from->isFuture()) {
            return true;
        }

        return false;
    }

    public function showOnboarding()
    {
        if (empty(config('leadgen.onboarding'))) {
            return false;
        }

        if ($this->isAdmin()) {
            return false;
        }

        $from = Carbon::parse('2020-03-29');

        $show = $this->created_at->greaterThanOrEqualTo($from);

        if ($show && empty($this->onboarding)) {
            return true;
        }

        return $show && !$this->onboarding->complete;
    }

    /**
     * Check if email verification is needed
     *
     * @return boolean
     */
    public function isEmailVerificationNeeded(): bool
    {
        if ($this->verified) {
            return false;
        }

        if ($this->isGoogleuser()) {
            return false;
        }

        if ($this->plan()->isFreeTrialPlan() && UserSettings::make()->isEmailVerificationEnabled()) {
            return true;
        }

        if ($this->plan()->isFreeTrialPlan() && !UserSettings::make()->isEmailVerificationEnabled()) {
            return false;
        }

        return false;
    }

    /**
     * Check if email verification is sent
     *
     * @return boolean
     */
    public function isEmailVerificationSent(): bool
    {
        if (
            $this->plan()->isFreeTrialPlan() && UserSettings::make()->isEmailVerificationEnabled() ||
            !UserSettings::make()->isEmailVerificationEnabled()
        ) {
            return true;
        }

        if (!$this->plan()->isFreeTrialPlan()) {
            return true;
        }

        return false;
    }

    /**
     * Check if two-factor authentication is needed for this user
     *
     * @param Plan|null $plan
     * @return boolean
     */
    public function isTwoFactorNeeded(?Plan $plan): bool
    {
        //if somehow $plan is null
        if ($plan === null) {
            return true;
        }

        //if in trial 2FA not needed
        if ($this->plan()->in_trial) {
            return false;
        }

        //if in trial 2FA not needed
        if (
            $plan->isPaddlePlan() &&
            in_array($this->getSubscription()->status, [PaddleSubscriptionStatusEnum::TRIALING], true)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get the time period to filter
     *
     * @param User|null $user
     * @param string|null $timePeriod
     * @return array|null
     */
    public function getTimePeriod(User $user = null, string $timePeriod = null): ?array
    {
        switch ($timePeriod) {
            case TimePeriodsEnum::MONTHLY:
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth(),
                ];
            case TimePeriodsEnum::YEARLY:
                return [
                    'start' => now()->startOfYear(),
                    'end' => now()->endOfYear(),
                ];
            case TimePeriodsEnum::AS_PER_PLAN:
                $subscription = $user ? $user->getSubscription() : $this->getSubscription();
                if (empty($subscription)) {
                    return null;
                } else {
                    return [
                        'start' => $subscription->created_at,
                        'end' => $subscription->next_bill_date ? $subscription->next_bill_date :
                        ($subscription->paused_from ? $subscription->paused_from : $subscription->ends_at)
                    ];
                }
            default:
                return null;
        }
    }

    /**
     * Get total number of lead proofs associated with forms created by the user
     *
     * @param string|null $timePeriod
     * @return int
     */
    public function leadProofsCount(string $timePeriod = null): int
    {
        $leadProofs = $this->forms->flatMap(function ($form) {
            return $form->formVariants->flatMap(function ($formVariant) {
                return $formVariant->leadProofs;
            });
        });

        $timePeriodRange = $this->getTimePeriod(null, $timePeriod);
        if ($timePeriodRange) {
            $leadProofs = $leadProofs->whereBetween('created_at', [
                $timePeriodRange['start'],
                $timePeriodRange['end']
            ]);
        }

        return $leadProofs->count();
    }

    /**
     * Get total number of partial leads created by the user
     *
     * @param string|null $timePeriod
     * @param User $user
     * @return int
     */
    public function partialLeadsCount(User $user, string $timePeriod = null): int
    {
        $partialLeads = $user->userLeads()->where('is_partial', true);

        $timePeriodRange = $this->getTimePeriod($user, $timePeriod);
        if ($timePeriodRange) {
            $partialLeads = $partialLeads->whereBetween('form_leads.created_at', [
                $timePeriodRange['start'],
                $timePeriodRange['end']
            ]);
        }

        return $partialLeads->count();
    }

    /**
     * Get total number of leads created by the user
     * @param string|null $timePeriod
     * @param User $user
     * @return int
     */
    public function leadsCount(User $user, string $timePeriod = null): int
    {
        $completeLeads = $user->userLeads()->where('is_partial', false);

        $timePeriodRange = $this->getTimePeriod($user, $timePeriod);
        if ($timePeriodRange) {
            $completeLeads = $completeLeads->whereBetween('form_leads.created_at', [
                $timePeriodRange['start'],
                $timePeriodRange['end']
            ]);
        }

        return $completeLeads->count();
    }

    /**
     * check if user has lead proof feature & properties
     *
     * @return boolean
     */
    public function canCreateLeadProof(): bool
    {
        $featureSlug = FeatureEnum::LEAD_PROOFS;
        $operator = OperatorsEnum::AND;
        $inputArray = array(
            [
                'limitation_title' => FeaturePropertyEnum::NO_OF_LEAD_PROOFS,
                'limitation_value' => $this->leadProofsCount(), // over all count
                'limitation_valueMonthly' => $this->leadProofsCount(TimePeriodsEnum::MONTHLY), // monthly count
                'limitation_valueYearly' => $this->leadProofsCount(TimePeriodsEnum::YEARLY), // yearly count
                'limitation_valueAsPerPlan' => $this->leadProofsCount(TimePeriodsEnum::AS_PER_PLAN), // as per plan
                'compare' => OperatorsEnum::LT
            ]
        );

        if ($this->isAdmin() || $this->isSuperCustomer()) {
            return true;
        } else {
            return $this->plan()->hasFeature($featureSlug, $operator, $inputArray);
        }
    }

    /**
     * check if user has partial lead feature & properties
     * @param $user
     * @return boolean
     */
    public function canCreatePartialLead(User $user): bool
    {
        $featureSlug = FeatureEnum::PARTIAL_LEADS;
        $operator = OperatorsEnum::AND;
        $inputArray = array(
            [
                'limitation_title' => FeaturePropertyEnum::NO_OF_PARTIAL_LEADS,
                'limitation_value' => $this->partialLeadsCount($user), // over all count
                'limitation_valueMonthly' => $this->partialLeadsCount($user, TimePeriodsEnum::MONTHLY),
                'limitation_valueYearly' => $this->partialLeadsCount($user, TimePeriodsEnum::YEARLY),
                'limitation_valueAsPerPlan' => $this->partialLeadsCount($user, TimePeriodsEnum::AS_PER_PLAN),
                'compare' => OperatorsEnum::LT
            ]
        );

        if ($user->isAdmin() || $user->isSuperCustomer()) {
            return true;
        } else {
            return $user->plan()->hasFeature($featureSlug, $operator, $inputArray);
        }
    }

    /**
     * check if user has lead feature & properties
     * @param $user
     * @return boolean
     */
    public function canCreateLeads(User $user): bool
    {
        $featureSlug = FeatureEnum::LEADS;
        $operator = OperatorsEnum::AND;
        $inputArray = array(
            [
                'limitation_title' => FeaturePropertyEnum::NO_OF_LEADS,
                'limitation_value' => $this->leadsCount($user), // over all count
                'limitation_valueMonthly' => $this->leadsCount($user, TimePeriodsEnum::MONTHLY),
                'limitation_valueYearly' => $this->leadsCount($user, TimePeriodsEnum::YEARLY),
                'limitation_valueAsPerPlan' => $this->leadsCount($user, TimePeriodsEnum::AS_PER_PLAN),
                'compare' => OperatorsEnum::LT
            ]
        );

        if ($user->isAdmin() || $user->isSuperCustomer()) {
            return true;
        } else {
            return $user->plan()->hasFeature($featureSlug, $operator, $inputArray);
        }
    }
    /**
     * check if user has lead proof feature & properties
     *
     * @return boolean
     */
    public function canUseTrustedForm(): bool
    {
        $featureSlug = FeatureEnum::TRUSTEDFORM;
        $operator = OperatorsEnum::AND;
        $inputArray = array(
            [
                'limitation_title' => FeaturePropertyEnum::ENABLE_TRUSTEDFORM,
                'limitation_value' => null,
                'limitation_valueMonthly' => TimePeriodsEnum::NONE,
                'limitation_valueYearly' =>  TimePeriodsEnum::NONE,
                'limitation_valueAsPerPlan' =>  TimePeriodsEnum::NONE,
                'compare' => OperatorsEnum::LT
            ]
        );

        if ($this->isAdmin() || $this->isSuperCustomer()) {
            return true;
        } else {
            return $this->plan()->hasFeature($featureSlug, $operator, $inputArray);
        }
    }
}
