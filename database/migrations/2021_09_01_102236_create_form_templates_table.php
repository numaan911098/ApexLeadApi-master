<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('media_id')->unsigned();
            $table->string('ref_id')->unique();
            $table->bigInteger('form_id')->unsigned();
            $table->bigInteger('from_user_id')->unsigned();
            $table->bigInteger('form_variant_id')->unsigned()->nullable();
            $table->string('template_id');
            $table->timestamps();

            $table->foreign('media_id')
                ->references('id')
                ->on('media')
                ->onDelete('cascade');
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
        Schema::dropIfExists('form_templates');
    }
}
