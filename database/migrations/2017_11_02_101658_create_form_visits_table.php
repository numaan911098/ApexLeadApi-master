<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ipAddress('ip');
            $table->integer('times');
            $table->bigInteger('form_id')->unsigned();
            $table->foreign('form_id')
            ->references('id')
            ->on('forms')
            ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_visits');
    }
}
