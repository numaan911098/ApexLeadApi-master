<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVariantIdFormSteps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_steps', function (Blueprint $table) {
            $table->bigInteger('form_variant_id')->unsigned()->nullable();
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
        Schema::table('form_steps', function (Blueprint $table) {
            //
        });
    }
}
