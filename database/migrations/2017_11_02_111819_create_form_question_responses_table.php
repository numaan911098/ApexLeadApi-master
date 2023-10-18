<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormQuestionResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_question_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('response');
            $table->bigInteger('form_lead_id')->unsigned();
            $table->bigInteger('form_question_id')->unsigned();
            $table->foreign('form_lead_id')
            ->references('id')
            ->on('form_leads')
            ->onDelete('cascade');
            $table->foreign('form_question_id')
            ->references('id')
            ->on('form_questions')
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
        Schema::dropIfExists('form_question_responses');
    }
}
