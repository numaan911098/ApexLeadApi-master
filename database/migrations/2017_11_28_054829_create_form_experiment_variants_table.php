<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormExperimentVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_experiment_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('weight');
            $table->integer('usage');
            $table->timestamps();
            $table->bigInteger('form_experiment_id')->unsigned();
            $table->bigInteger('form_variant_id')->unsigned();

            $table->foreign('form_experiment_id')
            ->references('id')
            ->on('form_experiments')
            ->onDelete('cascade');

            $table->foreign('form_variant_id')
            ->references('id')
            ->on('form_variants')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_experiment_variants');
    }
}
