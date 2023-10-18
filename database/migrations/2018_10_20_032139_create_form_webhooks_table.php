<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_webhooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->boolean('enable');
            $table->text('url');
            $table->text('format');
            $table->string('method');
            $table->text('fields_map')->nullable();
            $table->timestamps();
            $table->bigInteger('form_id')->unsigned();
            $table->bigInteger('form_variant_id')->unsigned()->nullable();

            $table->foreign('form_id')
            ->references('id')
            ->on('forms')
            ->onDelete('cascade');

            $table->foreign('form_variant_id')
            ->references('id')
            ->on('form_variants')
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
        Schema::dropIfExists('form_webhooks');
    }
}
