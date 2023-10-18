<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormStepElementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_step_elements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('number');
            $table->string('type');
            $table->mediumText('config');
            $table->timestamps();
            $table->bigInteger('form_step_id')->unsigned();
            
            $table->foreign('form_step_id')
                ->references('id')
                ->on('form_steps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_step_elements');
    }
}
