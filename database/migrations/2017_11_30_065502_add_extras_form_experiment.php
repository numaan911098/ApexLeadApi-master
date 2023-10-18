<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtrasFormExperiment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_experiments', function (Blueprint $table) {
            $table->string('title');
            $table->text('note')->nullable();
            $table->datetime('started_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_experiments', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('note');
            $table->dropColumn('started_at');
        });
    }
}
