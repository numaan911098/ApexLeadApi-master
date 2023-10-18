<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOneToolUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('one_tool_users', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('plan_id')->unsigned();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('role_type')->nullable();
            $table->string('status')->nullable();
            $table->boolean('in_trial');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
                ->onDelete('cascade');

            $table->timestamps();
            $table->primary(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('one_tool_users');
    }
}
