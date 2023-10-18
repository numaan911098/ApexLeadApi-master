<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtrasToFormVisits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_visits', function (Blueprint $table) {
            $table->dropColumn('times');
            $table->string('device_type')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->string('source_url', 512)->nullable();

            $table->bigInteger('visitor_id')->unsigned()->nullable();
            $table->foreign('visitor_id')
            ->references('id')
            ->on('visitors')
            ->onDelete('cascade');

            $table->bigInteger('form_experiment_id')->unsigned()->nullable();
            $table->foreign('form_experiment_id')
            ->references('id')
            ->on('form_experiments')
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
        Schema::table('form_visits', function (Blueprint $table) {
            $table->integer('times');
            $table->dropColumn('device_type');
            $table->dropColumn('os');
            $table->dropColumn('browser');
            $table->dropColumn('source_url');

            $table->dropForeign(['visitor_id']);
            $table->dropColumn('visitor_id');

            $table->dropForeign(['form_experiment_id']);
            $table->dropColumn('form_experiment_id');
        });
    }
}
