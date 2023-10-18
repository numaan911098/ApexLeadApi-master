<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\Paddle\PaddlePlansEnum;

class UpdateProTrialPlanTagId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $proTrialPlan = Plan::where('public_id', PaddlePlansEnum::PRO_TRIAL)->first();

        if (!$proTrialPlan) {
            return;
        }

        $proTrialPlan->tag_id = 19;
        $proTrialPlan->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $proTrialPlan = Plan::where('public_id', PaddlePlansEnum::PRO_TRIAL)->first();

        if (!$proTrialPlan) {
            return;
        }

        $proTrialPlan->tag_id = null;
        $proTrialPlan->save();
    }
}
