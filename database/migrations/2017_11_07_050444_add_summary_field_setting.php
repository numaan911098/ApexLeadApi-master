<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSummaryFieldSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_settings', function (Blueprint $table) {
            $table->boolean('steps_summary');
            $table->integer('response_limit');
            $table->boolean('enable_thankyou_url');
            $table->boolean('enable_google_recaptcha');
            $table->text('thankyou_message')->nullable();
            $table->text('thankyou_url')->nullable();

            $table->text('domains')->nullable()->change();
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
            $table->dropColumn('steps_summary');
            $table->dropColumn('response_limit');
            $table->dropColumn('enable_thankyou_url');
            $table->dropColumn('enable_google_recaptcha');
            $table->dropColumn('thankyou_message');
            $table->dropColumn('thankyou_url');
        });
    }
}
