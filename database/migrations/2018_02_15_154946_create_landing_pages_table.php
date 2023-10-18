<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLandingPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('keywords');
            $table->text('description');
            $table->mediumText('content');
            $table->text('config');
            $table->timestamps();
            $table->softDeletes();

            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('landing_page_template_id')->unsigned();

            $table->foreign('created_by')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');

            $table->foreign('landing_page_template_id')
            ->references('id')
            ->on('landing_page_templates')
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
        Schema::dropIfExists('landing_pages');
    }
}
