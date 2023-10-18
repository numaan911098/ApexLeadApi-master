<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ipAddress('ip');
            $table->string('source_url', 512);
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
        Schema::dropIfExists('form_leads');
    }
}
