<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueConstraintsGoogleRecaptchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('google_recaptcha_keys', function (Blueprint $table) {
            $table->dropUnique('google_recaptcha_keys_site_key_unique');
            $table->dropUnique('google_recaptcha_keys_secret_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('google_recaptcha_keys', function (Blueprint $table) {
            $table->string('site_key')->unique();
            $table->string('secret_key')->unique();
        });
    }
}
