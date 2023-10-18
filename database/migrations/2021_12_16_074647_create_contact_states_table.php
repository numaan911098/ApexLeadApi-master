<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_states', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('enable')->default(1);
            $table->text('landingpage_id')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('form_id')->unsigned();
            $table->bigInteger('form_variant_id')->unsigned()->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('contact_states');
    }
}
