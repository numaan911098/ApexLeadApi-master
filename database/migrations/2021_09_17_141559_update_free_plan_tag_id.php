<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\PlansEnum;

class UpdateFreePlanTagId extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $freePlan = Plan::where('public_id', PlansEnum::FREE)->first();

        if (!$freePlan) {
            return;
        }

        $freePlan->tag_id = 1;
        $freePlan->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $freePlan = Plan::where('public_id', PlansEnum::FREE)->first();

        if (!$freePlan) {
            return;
        }

        $freePlan->tag_id = null;
        $freePlan->save();
    }
}
