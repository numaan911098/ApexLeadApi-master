<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLandingPageVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('landing_page_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ipAddress('ip');
            $table->string('device_type')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->string('source_url', 512)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_name')->nullable();
            $table->string('robot_name')->nullable();
            $table->boolean('is_robot');
            $table->timestamps();

            $table->bigInteger('landing_page_id')->unsigned();
            $table->bigInteger('visitor_id')->unsigned();

            $table->foreign('visitor_id')
            ->references('id')
            ->on('visitors')
            ->onDelete('cascade');

            $table->foreign('landing_page_id')
            ->references('id')
            ->on('landing_pages')
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
        Schema::dropIfExists('landing_page_visits');
    }
}
