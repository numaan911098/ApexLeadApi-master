<?php

use Illuminate\Database\Migrations\Migration;
use App\Plan;
use App\Enums\Paddle\PaddlePlansEnum;

class UpdateProPlanTagId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $proPlan = Plan::where('public_id', PaddlePlansEnum::PRO)->first();

        if (!$proPlan) {
            return;
        }

        $proPlan->tag_id = 2;
        $proPlan->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $proPlan = Plan::where('public_id', PaddlePlansEnum::PRO)->first();

        if (!$proPlan) {
            return;
        }

        $proPlan->tag_id = null;
        $proPlan->save();
    }
}
