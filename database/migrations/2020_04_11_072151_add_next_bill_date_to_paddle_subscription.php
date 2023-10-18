<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNextBillDateToPaddleSubscription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paddle_subscriptions', function (Blueprint $table) {
            $table->dateTime('paused_from')->nullable();
            $table->dateTime('next_bill_date')->nullable();
            $table->string('currency')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paddle_subscriptions', function (Blueprint $table) {
            //
        });
    }
}
