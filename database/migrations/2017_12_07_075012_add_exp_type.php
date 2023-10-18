<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_experiments', function (Blueprint $table) {
            $table->bigInteger('form_experiment_type_id')->unsigned();
            $table->foreign('form_experiment_type_id')
            ->references('id')
            ->on('form_experiment_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_experiments', function (Blueprint $table) {
            $table->dropForeign(['form_experiment_type_id']);
            $table->dropColumn('form_experiment_type_id');
        });
    }
}
