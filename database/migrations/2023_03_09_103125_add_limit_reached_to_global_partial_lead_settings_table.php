<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLimitReachedToGlobalPartialLeadSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_partial_lead_settings', function (Blueprint $table) {
            $table->boolean('limit_reached')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('global_partial_lead_settings', function (Blueprint $table) {
            $table->dropColumn('limit_reached');
        });
    }
}
