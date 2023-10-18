<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Paddle\PaddlePlansEnum;
use App\Enums\StripePlansEnum;
use App\Enums\OneToolPlansEnum;
use App\Enums\OperatorsEnum;
use App\Enums\PlansEnum;
use App\Enums\TimePeriodsEnum;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     *
     * @var array
     *
     */
    protected $fillable = [
        'title',
        'public_id',
        'stripe_plan_id',
        'paddle_plan_id',
        'external_checkout_enabled',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'stripe_plan_id',
    ];

    /**
     * Check plan type by it's public id only.
     *
     * @param string|null $plan Plan public id.
     * @return boolean
     */
    public function isPaddlePlan(?string $plan = null): bool
    {
        if (empty($plan)) {
            $plan = $this->public_id;
        }
        return in_array($plan, PaddlePlansEnum::getConstants(), true);
    }

    /**
     * Check plan type by it's public id only.
     *
     * @param string|null $plan Plan public id.
     * @return boolean
     */
    public function isStripePlan(?string $plan = null): bool
    {
        if (empty($plan)) {
            $plan = $this->public_id;
        }

        return in_array($plan, StripePlansEnum::getConstants(), true);
    }

    /**
     * Check plan type by it's public id only.
     *
     * @param string|null $plan Plan public id.
     * @return boolean
     */
    public function isOneToolPlan(?string $plan = null): bool
    {
        if (empty($plan)) {
            $plan = $this->public_id;
        }

        return in_array($plan, OneToolPlansEnum::getConstants(), true);
    }

    /**
     * Check free plan.
     *
     * @return boolean
     */
    public function isFreePlan()
    {
        return $this->public_id === PlansEnum::FREE;
    }

    /**
     * Check pro trial plan.
     *
     * @return boolean
     */
    public function isFreeTrialPlan(): bool
    {
        return $this->public_id === PlansEnum::FREE_TRIAL;
    }

    /**
     * Check pro trial plan.
     *
     * @return boolean
     */
    public function isProTrialPlan(): bool
    {
        return $this->public_id === PaddlePlansEnum::PRO_TRIAL;
    }

    /**
     * Check pro plan.
     *
     * @return boolean
     */
    public function isProTypePlan(): bool
    {
        return in_array($this->public_id, [
            PaddlePlansEnum::PRO, PaddlePlansEnum::PRO_ANNUAL,
            PaddlePlansEnum::PRO_TRIAL, PaddlePlansEnum::PRO_ANNUAL_TRIAL
        ]);
    }

    /**
     * Check scale plan.
     *
     * @return boolean
     */
    public function isScaleTypePlan(): bool
    {
        return in_array($this->public_id, [
            PaddlePlansEnum::SCALE, PaddlePlansEnum::SCALE_ANNUAL,
            PaddlePlansEnum::SCALE_TRIAL, PaddlePlansEnum::SCALE_ANNUAL_TRIAL
        ]);
    }

    /**
     * Check enterprise plan.
     *
     * @return boolean
     */
    public function isEnterpriseTypePlan(): bool
    {
        return in_array($this->public_id, [
            PaddlePlansEnum::ENTERPRISE, PaddlePlansEnum::ENTERPRISE_ANNUAL,
            PaddlePlansEnum::ENTERPRISE_TRIAL, PaddlePlansEnum::ENTERPRISE_ANNUAL_TRIAL
        ]);
    }

    /**
     * Get plan features.
     *
     * @return BelongsToMany
     */
    public function planFeatures(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')->with(['featureProperties' => function ($query) {
            $query->join(
                'plan_feature_properties',
                'feature_properties.id',
                '=',
                'plan_feature_properties.feature_property_id'
            );
            $query->join(
                'plan_features',
                'plan_features.id',
                '=',
                'plan_feature_properties.plan_feature_id'
            );
            $query->where('plan_features.plan_id', '=', $this->id);
        }]);
    }

    /**
     * Check if plan has feature or feature properties
     * @param string $featureSlug
     * @param string $operator
     * @param array $inputArray
     * @return boolean
     */
    public function hasFeature(string $featureSlug, string $operator, array $inputArray): bool
    {
        $planFeatures = $this->planFeatures->where('slug', $featureSlug)->first();
        if (!$planFeatures) {
            return false;
        }

        $planFeatureProperties = $planFeatures->featureProperties->where('feature_id', $planFeatures->id);
        if (!$planFeatureProperties->count()) {
            return false;
        }

        $result = [];
        foreach ($inputArray as $input) {
            foreach ($planFeatureProperties as $package) {
                $limitationValue = $input['limitation_value'];
                if (
                    $package->reset_period === TimePeriodsEnum::MONTHLY &&
                    isset($input['limitation_valueMonthly'])
                ) {
                    $limitationValue = $input['limitation_valueMonthly'];
                } elseif (
                    $package->reset_period === TimePeriodsEnum::YEARLY &&
                    isset($input['limitation_valueYearly'])
                ) {
                    $limitationValue = $input['limitation_valueYearly'];
                } elseif (
                    $package->reset_period === TimePeriodsEnum::AS_PER_PLAN &&
                    isset($input['limitation_valueAsPerPlan'])
                ) {
                    $limitationValue = $input['limitation_valueAsPerPlan'];
                }

                $result[] = $this->dynComparison(
                    $limitationValue,
                    $package->value,
                    $input['compare']
                );
            }
        }

        return $operator === 'OR' ? in_array(true, $result) : (in_array(false, $result) ? false : true);
    }

    /**
     * calculate result of feature properties
     * @param string|null $inputValue
     * @param string $comparisonValue
     * @param string $operator
     * @return boolean
     */
    public function dynComparison(?string $inputValue, string $comparisonValue, string $operator): bool
    {
        $operators = [
            OperatorsEnum::EQ => function ($a, $b) {
                return $a == $b;
            },
            OperatorsEnum::LT => function ($a, $b) {
                return $a < $b;
            },
            OperatorsEnum::LTE => function ($a, $b) {
                return $a <= $b;
            },
            OperatorsEnum::GT => function ($a, $b) {
                return $a > $b;
            },
            OperatorsEnum::GTE => function ($a, $b) {
                return $a >= $b;
            },
        ];

        if (!isset($operators[$operator])) {
            return false;
        }

        return $operators[$operator]($inputValue, $comparisonValue);
    }
}
