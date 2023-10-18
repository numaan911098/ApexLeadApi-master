<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormEmailNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_email_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('subject')->nullable();
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('from_name')->nullable();
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
        Schema::dropIfExists('form_email_notifications');
    }
}
