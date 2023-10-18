<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\Paddle\PaddlePlansEnum;

class UpdateTagIdToYearlyPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $proYearlyPlan = Plan::where('public_id', PaddlePlansEnum::PRO_ANNUAL)->first();

        if ($proYearlyPlan) {
            $proYearlyPlan->tag_id = 2;
            $proYearlyPlan->save();
        }

        $scaleYearlyPlan = Plan::where('public_id', PaddlePlansEnum::SCALE_ANNUAL)->first();

        if ($scaleYearlyPlan) {
            $scaleYearlyPlan->tag_id = 20;
            $scaleYearlyPlan->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $proYearlyPlan = Plan::where('public_id', PaddlePlansEnum::PRO_ANNUAL)->first();

        if ($proYearlyPlan) {
            $proYearlyPlan->tag_id = null;
            $proYearlyPlan->save();
        }

        $scaleYearlyPlan = Plan::where('public_id', PaddlePlansEnum::SCALE_ANNUAL)->first();

        if ($scaleYearlyPlan) {
            $scaleYearlyPlan->tag_id = null;
            $scaleYearlyPlan->save();
        }
    }
}
