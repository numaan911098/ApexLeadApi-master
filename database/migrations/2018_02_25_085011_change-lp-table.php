<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('keywords')->nullable()->change();
            $table->string('description')->nullable()->change();
            $table->dropColumn('config');
            $table->dropColumn('content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('landing_pages', function (Blueprint $table) {
        });
    }
}
