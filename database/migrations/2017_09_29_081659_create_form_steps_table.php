<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('number')->default(1);
            $table->bigInteger('form_id')->unsigned();
            $table->timestamps();
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
        Schema::dropIfExists('form_steps');
    }
}
