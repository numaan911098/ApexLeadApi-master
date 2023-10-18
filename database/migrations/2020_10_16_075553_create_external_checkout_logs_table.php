<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalCheckoutLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_checkout_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('response')->nullable();
            $table->timestamps();
            $table->bigInteger('external_checkout_id')->unsigned();
            $table->foreign('external_checkout_id')
                ->references('id')
                ->on('external_checkouts')
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
        Schema::dropIfExists('external_checkout_logs');
    }
}
