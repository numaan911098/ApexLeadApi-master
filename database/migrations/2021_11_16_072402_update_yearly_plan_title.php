<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\Paddle\PaddlePlansEnum;

class UpdateYearlyPlanTitle extends Migration
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
            $proYearlyPlan->title = 'Yearly Pro';
            $proYearlyPlan->save();
        }

        $scaleYearlyPlan = Plan::where('public_id', PaddlePlansEnum::SCALE_ANNUAL)->first();

        if ($scaleYearlyPlan) {
            $scaleYearlyPlan->title = 'Yearly Scale';
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
            $proYearlyPlan->title = 'Pro';
            $proYearlyPlan->save();
        }

        $scaleYearlyPlan = Plan::where('public_id', PaddlePlansEnum::SCALE_ANNUAL)->first();

        if ($scaleYearlyPlan) {
            $scaleYearlyPlan->title = 'Scale';
            $scaleYearlyPlan->save();
        }
    }
}
