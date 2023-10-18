<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoogleRecaptchaKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_recaptcha_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('site_key')->unique();
            $table->string('secret_key')->unique();
            $table->string('type');
            $table->bigInteger('created_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('google_recaptcha_keys');
    }
}
