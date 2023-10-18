<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormExperimentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_experiments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('ended_at')->nullable();

            $table->bigInteger('form_id')->unsigned();
            $table->foreign('form_id')
            ->references('id')
            ->on('forms')
            ->onDelete('cascade');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_experiments');
    }
}
