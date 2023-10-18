<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormHiddenFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_hidden_fields', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('default_value')->nullable();
            $table->boolean('capture_from_url_parameter');
            $table->timestamps();
            $table->bigInteger('form_id')->unsigned();
            $table->bigInteger('form_variant_id')->unsigned();

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
        Schema::dropIfExists('form_hidden_fields');
    }
}
