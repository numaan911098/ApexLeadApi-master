<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormPartialLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_partial_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('enabled');
            $table->bigInteger('form_id')->unsigned();
            $table->string('consent_type')->nullable();
            $table->foreign('form_id')
            ->references('id')
            ->on('forms')
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
        Schema::dropIfExists('form_partial_leads');
    }
}
