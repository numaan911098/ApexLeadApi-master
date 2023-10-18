<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIpinfoOfVisit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_visits', function (Blueprint $table) {
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('state')->nullable();
            $table->string('state_code')->nullable();
            $table->string('city')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('timezone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_visits', function (Blueprint $table) {
            //
        });
    }
}
