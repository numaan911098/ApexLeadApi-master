<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpIdToForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->bigInteger('current_experiment_id')->unsigned()->nullable();
            $table->softDeletes();

            $table->foreign('current_experiment_id')
            ->references('id')
            ->on('form_experiments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropForeign(['current_experiment_id']);
            $table->dropColumn('current_experiment_id');

            $table->dropColumn('deleted_at');
        });
    }
}
