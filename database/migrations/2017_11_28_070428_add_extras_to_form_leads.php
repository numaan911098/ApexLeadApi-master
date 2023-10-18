<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtrasToFormLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_leads', function (Blueprint $table) {
            $table->dropColumn('ip');
            $table->dropColumn('source_url');

            $table->bigInteger('form_visit_id')->unsigned()->nullable();
            $table->foreign('form_visit_id')
            ->references('id')
            ->on('form_visits')
            ->onDelete('cascade');

            $table->bigInteger('form_experiment_id')->unsigned()->nullable();
            $table->foreign('form_experiment_id')
            ->references('id')
            ->on('form_experiments')
            ->onDelete('cascade');

            $table->bigInteger('form_variant_id')->unsigned()->nullable();
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
        Schema::table('form_leads', function (Blueprint $table) {
            $table->ipAddress('ip');
            $table->string('source_url', 512);

            $table->dropForeign(['form_visit_id']);
            $table->dropColumn('form_visit_id');

            $table->dropForeign(['form_experiment_id']);
            $table->dropColumn('form_experiment_id');

            $table->dropForeign(['form_variant_id']);
            $table->dropColumn('form_variant_id');
        });
    }
}
