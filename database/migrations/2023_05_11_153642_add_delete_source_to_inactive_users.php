<?php

use App\Enums\RequestSourceEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeleteSourceToInactiveUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inactive_users', function (Blueprint $table) {
            $table->string('delete_source')->default(RequestSourceEnum::SOURCE_COMMAND);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inactive_users', function (Blueprint $table) {
            $table->dropColumn('delete_source');
        });
    }
}
