<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Plan;
use App\Enums\Paddle\PaddlePlansEnum;

class UpdatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $proPlan = Plan::where('public_id', PaddlePlansEnum::PRO)->first();

        if ($proPlan) {
            $proPlan->form_base_limit = 0;
            $proPlan->save();
        }

        $scalePlan = Plan::where('public_id', PaddlePlansEnum::SCALE)->first();

        if ($scalePlan) {
            $scalePlan->form_base_limit = 0;
            $scalePlan->save();
        }

        $proTrialPlan = Plan::where('public_id', PaddlePlansEnum::PRO_TRIAL)->first();

        if ($proTrialPlan) {
            $proTrialPlan->form_base_limit = 0;
            $proTrialPlan->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $proPlan = Plan::where('public_id', PaddlePlansEnum::PRO)->first();

        if ($proPlan) {
            $proPlan->form_base_limit = 1;
            $proPlan->save();
        }

        $scalePlan = Plan::where('public_id', PaddlePlansEnum::SCALE)->first();

        if ($scalePlan) {
            $scalePlan->form_base_limit = 1; // back to previous value
            $scalePlan->save();
        }

        $proTrialPlan = Plan::where('public_id', PaddlePlansEnum::PRO_TRIAL)->first();

        if ($proTrialPlan) {
            $proTrialPlan->form_base_limit = 1; // back to previous value
            $proTrialPlan->save();
        }
    }
}
