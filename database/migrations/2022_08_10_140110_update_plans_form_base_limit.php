<?php

use App\Enums\Paddle\PaddlePlansEnum;
use App\Plan;
use Illuminate\Database\Migrations\Migration;

class UpdatePlansFormBaseLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $proAnnualPlan = Plan::where('public_id', PaddlePlansEnum::PRO_ANNUAL)->first();

        if ($proAnnualPlan) {
            $proAnnualPlan->form_base_limit = 0;
            $proAnnualPlan->save();
        }

        $scaleAnnualPlan = Plan::where('public_id', PaddlePlansEnum::SCALE_ANNUAL)->first();

        if ($scaleAnnualPlan) {
            $scaleAnnualPlan->form_base_limit = 0;
            $scaleAnnualPlan->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $proAnnualPlan = Plan::where('public_id', PaddlePlansEnum::PRO_ANNUAL)->first();

        if ($proAnnualPlan) {
            $proAnnualPlan->form_base_limit = 1; // back to previous value
            $proAnnualPlan->save();
        }

        $scaleAnnualPlan = Plan::where('public_id', PaddlePlansEnum::SCALE_ANNUAL)->first();

        if ($scaleAnnualPlan) {
            $scaleAnnualPlan->form_base_limit = 1; // back to previous value
            $scaleAnnualPlan->save();
        }
    }
}
