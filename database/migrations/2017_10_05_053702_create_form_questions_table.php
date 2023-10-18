<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('number');
            $table->string('config');
            $table->bigInteger('form_question_type_id')->unsigned();
            $table->bigInteger('form_step_id')->unsigned();
            $table->foreign('form_question_type_id')->references('id')
            ->on('form_question_types')->onDelete('cascade');
            $table->foreign('form_step_id')->references('id')
            ->on('form_steps')->onDelete('cascade');
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
        Schema::dropIfExists('form_questions');
    }
}
