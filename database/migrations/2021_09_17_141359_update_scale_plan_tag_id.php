<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\Paddle\PaddlePlansEnum;

class UpdateScalePlanTagId extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $scalePlan = Plan::where('public_id', PaddlePlansEnum::SCALE)->first();

        if (!$scalePlan) {
            return;
        }

        $scalePlan->tag_id = 20;
        $scalePlan->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $scalePlan = Plan::where('public_id', PaddlePlansEnum::SCALE)->first();

        if (!$scalePlan) {
            return;
        }

        $scalePlan->tag_id = null;
        $scalePlan->save();
    }
}
