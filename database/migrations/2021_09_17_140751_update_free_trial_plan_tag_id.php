<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\PlansEnum;

class UpdateFreeTrialPlanTagId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $freeTrialPlan = Plan::where('public_id', PlansEnum::FREE_TRIAL)->first();

        if (!$freeTrialPlan) {
            return;
        }

        $freeTrialPlan->tag_id = 19;
        $freeTrialPlan->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $freeTrialPlan = Plan::where('public_id', PlansEnum::FREE_TRIAL)->first();

        if (!$freeTrialPlan) {
            return;
        }

        $freeTrialPlan->tag_id = null;
        $freeTrialPlan->save();
    }
}
