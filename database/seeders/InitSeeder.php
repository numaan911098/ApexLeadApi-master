<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Facades\App\Services\Util;
use App\Enums\QuestionTypesEnum;
use App\Enums\FormVariantTypesEnum;
use App\Enums\FormExperimentTypesEnum;
use App\Enums\PlansEnum;
use App\Enums\StripePlansEnum;
use App\Enums\OneToolPlansEnum;
use App\Enums\Paddle\PaddlePlansEnum;
use App\Enums\RolesEnum;
use App\FormQuestionType;
use App\FormVariantType;
use App\FormExperimentType;
use App\FormStep;
use App\FormVariant;
use App\Form;
use App\FormLead;
use App\Plan;
use App\Role;
use App\User;
use App\Media;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Log;

class InitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (RolesEnum::getConstants() as $role) {
            Role::firstOrCreate([
                'name' => $role
            ], $this->getRole($role));
        }

        foreach (QuestionTypesEnum::getConstants() as $questionType) {
            FormQuestionType::firstOrCreate([
                'type' => $questionType
            ]);
        }

        foreach (FormVariantTypesEnum::getConstants() as $variantType) {
            FormVariantType::firstOrCreate([
                'type' => $variantType
            ]);
        }

        foreach (FormExperimentTypesEnum::getConstants() as $experimentType) {
            FormExperimentType::firstOrCreate([
                'type' => $experimentType
            ]);
        }

        foreach (PlansEnum::getConstants() as $plan) {
            $freePlan = Plan::firstOrCreate([
                'public_id' => $plan
            ], $this->getPlan($plan));

            $freePlan->title = $this->getPlan($plan)['title'];
            $freePlan->public_id = $plan;
            $freePlan->form_limit = $this->getPlan($plan)['form_limit'];
            $freePlan->form_base_limit = $this->getPlan($plan)['form_base_limit'];
            $freePlan->external_checkout_enabled = $this->getPlan($plan)['external_checkout_enabled'];
            $freePlan->save();
        }

        foreach (StripePlansEnum::getConstants() as $plan) {
            $stripePlan = Plan::firstOrCreate([
                'public_id' => $plan
            ], $this->getPlan($plan));

            $stripePlan->title = $this->getPlan($plan)['title'];
            $stripePlan->stripe_plan_id = $this->getPlan($plan)['stripe_plan_id'];
            $stripePlan->public_id = $plan;
            $stripePlan->form_limit = $this->getPlan($plan)['form_limit'];
            $stripePlan->form_base_limit = $this->getPlan($plan)['form_base_limit'];
            $stripePlan->save();
        }

        foreach (OneToolPlansEnum::getConstants() as $plan) {
            $oneToolPlan = Plan::firstOrCreate([
                'public_id' => $plan
            ], $this->getPlan($plan));

            $oneToolPlan->title = $this->getPlan($plan)['title'];
            $oneToolPlan->onetool_plan_id = $this->getPlan($plan)['onetool_plan_id'];
            $oneToolPlan->public_id = $plan;
            $oneToolPlan->form_limit = $this->getPlan($plan)['form_limit'];
            $oneToolPlan->form_base_limit = $this->getPlan($plan)['form_base_limit'];
            $oneToolPlan->save();
        }

        foreach (PaddlePlansEnum::getConstants() as $plan) {
            $paddlePlan = Plan::firstOrCreate([
                'public_id' => $plan
            ], $this->getPlan($plan));

            $paddlePlan->title = $this->getPlan($plan)['title'];
            $paddlePlan->paddle_plan_id = $this->getPlan($plan)['paddle_plan_id'];
            $paddlePlan->public_id = $plan;
            $paddlePlan->form_limit = $this->getPlan($plan)['form_limit'];
            $paddlePlan->form_base_limit = $this->getPlan($plan)['form_base_limit'];
            $paddlePlan->external_checkout_enabled = $this->getPlan($plan)['external_checkout_enabled'];
            $paddlePlan->save();
        }
    }

    private function getPlan($plan)
    {
        $proPlanId = config('leadgen.stripe_test_pro_plan_id');

        if (config('app.env') === 'production') {
            $proPlanId = config('leadgen.stripe_live_pro_plan_id');
        }

        return [
            PlansEnum::FREE => [
                'title' => 'Free',
                'public_id' => PlansEnum::FREE,
                'form_limit' => 0,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PlansEnum::FREE_TRIAL => [
                'title' => 'Free Trial',
                'public_id' => PlansEnum::FREE_TRIAL,
                'form_limit' => 20,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
                'trial_days' => 14,
            ],
            StripePlansEnum::PRO => [
                'title' => 'Pro',
                'stripe_plan_id' => $proPlanId,
                'public_id' => StripePlansEnum::PRO,
                'form_limit' => 100,
                'form_base_limit' => 1,
                'external_checkout_enabled' => false,
            ],
            OneToolPlansEnum::BASIC_LITE => [
                'title' => 'Basic Lite',
                'onetool_plan_id' => config('leadgen.onetool_basic_lite_plan_id'),
                'public_id' => OneToolPlansEnum::BASIC_LITE,
                'form_limit' => 3,
                'form_base_limit' => 3,
                'external_checkout_enabled' => false,
            ],
            OneToolPlansEnum::PRO_LITE => [
                'title' => 'Pro Lite',
                'onetool_plan_id' => config('leadgen.onetool_pro_lite_plan_id'),
                'public_id' => OneToolPlansEnum::PRO_LITE,
                'form_limit' => 3,
                'form_base_limit' => 3,
                'external_checkout_enabled' => false,
            ],
            OneToolPlansEnum::PRO => [
                'title' => 'Pro',
                'onetool_plan_id' => config('leadgen.onetool_pro_plan_id'),
                'public_id' => OneToolPlansEnum::PRO,
                'form_limit' => 20,
                'form_base_limit' => 3,
                'external_checkout_enabled' => false,
            ],
            PaddlePlansEnum::PRO => [
                'title' => 'Pro',
                'paddle_plan_id' => config('leadgen.paddle_pro_plan_id'),
                'public_id' => PaddlePlansEnum::PRO,
                'form_limit' => 20,
                'form_base_limit' => 1,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::PRO_ANNUAL => [
                'title' => 'Yearly Pro',
                'paddle_plan_id' => config('leadgen.paddle_pro_annual_plan_id'),
                'public_id' => PaddlePlansEnum::PRO_ANNUAL,
                'form_limit' => 20,
                'form_base_limit' => 1,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::PRO2 => [
                'title' => 'Pro 2',
                'paddle_plan_id' => config('leadgen.paddle_pro2_plan_id'),
                'public_id' => PaddlePlansEnum::PRO2,
                'form_limit' => 100,
                'form_base_limit' => 1,
                'external_checkout_enabled' => false,
            ],
            PaddlePlansEnum::PRO_TRIAL => [
                'title' => 'Pro Trial',
                'paddle_plan_id' => config('leadgen.paddle_pro_trial_plan_id'),
                'public_id' => PaddlePlansEnum::PRO_TRIAL,
                'form_limit' => 20,
                'form_base_limit' => 1,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::SCALE => [
                'title' => 'Scale',
                'paddle_plan_id' => config('leadgen.paddle_scale_plan_id'),
                'public_id' => PaddlePlansEnum::SCALE,
                'form_limit' => 100,
                'form_base_limit' => 1,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::SCALE_ANNUAL => [
                'title' => 'Yearly Scale',
                'paddle_plan_id' => config('leadgen.paddle_scale_annual_plan_id'),
                'public_id' => PaddlePlansEnum::SCALE_ANNUAL,
                'form_limit' => 100,
                'form_base_limit' => 1,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::ENTERPRISE => [
                'title' => 'Enterprise',
                'paddle_plan_id' => config('leadgen.paddle_enterprise_plan_id'),
                'public_id' => PaddlePlansEnum::ENTERPRISE,
                'form_limit' => 300,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::ENTERPRISE_ANNUAL => [
                'title' => 'Yearly Enterprise',
                'paddle_plan_id' => config('leadgen.paddle_enterprise_annual_plan_id'),
                'public_id' => PaddlePlansEnum::ENTERPRISE_ANNUAL,
                'form_limit' => 300,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::ENTERPRISE_TRIAL => [
                'title' => 'Enterprise Trial',
                'paddle_plan_id' => config('leadgen.paddle_enterprise_trial_plan_id'),
                'public_id' => PaddlePlansEnum::ENTERPRISE_TRIAL,
                'form_limit' => 300,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::ENTERPRISE_ANNUAL_TRIAL => [
                'title' => 'Yearly Enterprise Trial',
                'paddle_plan_id' => config('leadgen.paddle_enterprise_annual_trial_plan_id'),
                'public_id' => PaddlePlansEnum::ENTERPRISE_ANNUAL_TRIAL,
                'form_limit' => 300,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::SCALE_TRIAL => [
                'title' => 'Scale Trial',
                'paddle_plan_id' => config('leadgen.paddle_scale_trial_plan_id'),
                'public_id' => PaddlePlansEnum::SCALE_TRIAL,
                'form_limit' => 100,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::SCALE_ANNUAL_TRIAL => [
                'title' => 'Yearly Scale Trial',
                'paddle_plan_id' => config('leadgen.paddle_scale_annual_trial_plan_id'),
                'public_id' => PaddlePlansEnum::SCALE_ANNUAL_TRIAL,
                'form_limit' => 100,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::PRO_ANNUAL_TRIAL => [
                'title' => 'Yearly Pro Trial',
                'paddle_plan_id' => config('leadgen.paddle_pro_annual_trial_plan_id'),
                'public_id' => PaddlePlansEnum::PRO_ANNUAL_TRIAL,
                'form_limit' => 20,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
            PaddlePlansEnum::SINGLE_ANNUAL => [
                'title' => 'Single Plan',
                'paddle_plan_id' => config('leadgen.paddle_single_annual_plan_id'),
                'public_id' => PaddlePlansEnum::SINGLE_ANNUAL,
                'form_limit' => 1,
                'form_base_limit' => 0,
                'external_checkout_enabled' => true,
            ],
        ][$plan];
    }

    private function getRole($role)
    {
        return [
            RolesEnum::ADMIN => [
                'title' => 'Administrator',
                'name' => RolesEnum::ADMIN,
                'description' => 'Manages all aspects of leadgen.'
            ],
            RolesEnum::CUSTOMER => [
                'title' => 'Customer',
                'name' => RolesEnum::CUSTOMER,
                'description' => 'Leadgen customer.'
            ],
            RolesEnum::SUPER_CUSTOMER => [
                'title' => 'Super Customer',
                'name' => RolesEnum::SUPER_CUSTOMER,
                'description' => 'Leadgen super customer has access to all the features.'
            ]
        ][$role];
    }
}
