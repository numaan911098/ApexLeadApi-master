<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGlobalPartialLeadSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_partial_lead_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('enabled');
            $table->bigInteger('user_id')->unsigned();
            $table->string('consent_type')->nullable();
            $table->foreign('user_id')
            ->references('id')
            ->on('users')
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
        Schema::dropIfExists('global_partial_lead_settings');
    }
}
