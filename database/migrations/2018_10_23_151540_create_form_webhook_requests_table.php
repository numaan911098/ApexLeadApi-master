<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormWebhookRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_webhook_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('form_lead_id')->unsigned();
            $table->bigInteger('form_webhook_id')->unsigned();
            $table->integer('response_status')->nullable();
            $table->text('response_content')->nullable();
            $table->boolean('error');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('form_lead_id')
            ->references('id')
            ->on('form_leads')
            ->onDelete('cascade');

            $table->foreign('form_webhook_id')
            ->references('id')
            ->on('form_webhooks')
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
        Schema::dropIfExists('form_webhook_requests');
    }
}
