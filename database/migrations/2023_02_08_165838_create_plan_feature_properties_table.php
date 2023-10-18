<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanFeaturePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_feature_properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('plan_feature_id')->unsigned();
            $table->bigInteger('feature_property_id')->unsigned();
            $table->text('value')->nullable();
            $table->text('reset_period')->nullable();
            $table->timestamps();

            $table->foreign('plan_feature_id')->references('id')->on('plan_features')->onDelete('cascade');
            $table->foreign('feature_property_id')->references('id')->on('feature_properties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_feature_properties');
    }
}
