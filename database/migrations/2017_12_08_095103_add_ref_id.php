<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRefId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('ref_id')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropUnique(['ref_id']);
            $table->dropColumn('ref_id');
        });
    }
}
