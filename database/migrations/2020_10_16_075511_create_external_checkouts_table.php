<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalCheckoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_checkouts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('ref_id')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->text('fields');
            $table->string('redirect_url')->nullable();
            $table->boolean('login');
            $table->boolean('enable');
            $table->timestamps();
            $table->bigInteger('plan_id')->unsigned();

            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
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
        Schema::dropIfExists('external_checkouts');
    }
}
