<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLandingPageOptinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('landing_page_optins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('visitor_id')->unsigned();
            $table->bigInteger('landing_page_id')->unsigned();
            $table->bigInteger('landing_page_visit_id')->unsigned();
            $table->bigInteger('form_lead_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('visitor_id')
            ->references('id')
            ->on('visitors')
            ->onDelete('cascade');

            $table->foreign('landing_page_id')
            ->references('id')
            ->on('landing_pages')
            ->onDelete('cascade');

            $table->foreign('form_lead_id')
            ->references('id')
            ->on('form_leads')
            ->onDelete('cascade');

            $table->foreign('landing_page_visit_id')
            ->references('id')
            ->on('landing_page_visits')
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
        Schema::dropIfExists('landing_page_optins');
    }
}
