<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormVariantSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_variant_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('auto_navigation');
            $table->bigInteger('form_variant_id')->unsigned();
            $table->timestamps();

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
        Schema::dropIfExists('form_variant_settings');
    }
}
