<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrimTrailingZerosToFormSettingsTable extends Migration
{
    /**
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_settings', function (Blueprint $table) {
            $table->boolean('trim_trailing_zeros');
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
            $table->dropColumn('trim_trailing_zeros');
        });
    }
}
