<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUseragentFormVisits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_visits', function (Blueprint $table) {
            $table->text('user_agent')->nullable();
            $table->string('device_name')->nullable();
            $table->string('robot_name')->nullable();
            $table->boolean('is_robot');
            $table->bigInteger('form_variant_id')->unsigned();
            $table->foreign('form_variant_id')
            ->references('id')
            ->on('form_variants')
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
        Schema::table('form_visits', function (Blueprint $table) {
            $table->dropColumn('user_agent');
            $table->dropColumn('device_name')->nullable();
            $table->dropColumn('robot_name')->nullable();
            $table->dropColumn('is_robot');
            $table->dropForeign(['form_variant_id']);
            $table->dropColumn('form_variant_id');
        });
    }
}
