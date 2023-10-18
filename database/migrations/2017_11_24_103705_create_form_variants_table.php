<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->bigInteger('form_variant_type_id')->unsigned();
            $table->bigInteger('form_id')->unsigned();

            $table->foreign('form_variant_type_id')
            ->references('id')
            ->on('form_variant_types')
            ->onDelete('cascade');

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
        Schema::dropIfExists('form_variants');
    }
}
