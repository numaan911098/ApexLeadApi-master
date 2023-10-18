<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormTrackingEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('event');
            $table->bigInteger('form_id')->unsigned();
            $table->boolean('configured');
            $table->boolean('active');
            $table->text('script');
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
        Schema::dropIfExists('form_tracking_events');
    }
}
