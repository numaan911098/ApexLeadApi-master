<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormHiddenFieldResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_hidden_field_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('response')->nullable();
            $table->bigInteger('form_lead_id')->unsigned();
            $table->bigInteger('form_hidden_field_id')->unsigned();
            $table->timestamps();

            $table->foreign('form_lead_id')
            ->references('id')
            ->on('form_leads')
            ->onDelete('cascade');
            $table->foreign('form_hidden_field_id')
            ->references('id')
            ->on('form_hidden_fields')
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
        Schema::dropIfExists('form_hidden_field_responses');
    }
}
