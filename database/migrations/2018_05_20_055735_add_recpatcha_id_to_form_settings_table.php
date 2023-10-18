<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRecpatchaIdToFormSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_settings', function (Blueprint $table) {
            $table->bigInteger('google_recaptcha_key_id')->unsigned()->nullable();

            $table->foreign('google_recaptcha_key_id')
                ->references('id')
                ->on('google_recaptcha_keys')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_settings', function (Blueprint $table) {
            $table->dropForeign(['google_recaptcha_key_id']);
            $table->dropColumn('google_recaptcha_key_id');
        });
    }
}
