<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormVariantThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_variant_themes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->mediumText('general');
            $table->mediumText('typography');
            $table->mediumText('ui_elements');
            $table->mediumText('custom_css');
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
        Schema::dropIfExists('form_variant_themes');
    }
}
