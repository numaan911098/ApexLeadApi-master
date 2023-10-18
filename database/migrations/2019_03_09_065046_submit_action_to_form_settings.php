<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Enums\FormSubmitActionEnum;

class SubmitActionToFormSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_settings', function (Blueprint $table) {
            $table->string('submit_action')->default(FormSubmitActionEnum::MESSAGE);
            $table->boolean('post_data_to_url');
            $table->boolean('append_data_to_url');
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
            //
        });
    }
}
