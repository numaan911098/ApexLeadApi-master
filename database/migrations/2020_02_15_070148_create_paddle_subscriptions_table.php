<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaddleSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paddle_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->string('paddle_id');
            $table->string('paddle_plan');
            $table->integer('quantity');
            $table->string('paused_reason')->nullable();
            $table->datetime('paused_at')->nullable();
            $table->datetime('ends_at')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
            ->references('id')
            ->on('users')
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
        Schema::dropIfExists('paddle_subscriptions');
    }
}
