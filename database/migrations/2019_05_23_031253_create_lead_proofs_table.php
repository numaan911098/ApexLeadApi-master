<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadProofsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_proofs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('ref_id')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->boolean('show_firstpart_only');
            $table->boolean('show_timestamp');
            $table->boolean('show_country');
            $table->boolean('latest');
            $table->integer('count');
            $table->integer('delay');
            $table->timestamps();
            $table->bigInteger('form_variant_id')->unsigned();
            $table->bigInteger('form_question_id')->unsigned();

            $table->foreign('form_variant_id')
                ->references('id')
                ->on('form_variants')
                ->onDelete('cascade');

            $table->foreign('form_question_id')
                ->references('id')
                ->on('form_questions')
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
        Schema::dropIfExists('lead_proofs');
    }
}
