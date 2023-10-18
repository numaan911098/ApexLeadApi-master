<?php

use App\Enums\QuestionTypesEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddNumberToFormQuestionTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('form_question_types')->insertOrIgnore([
            'type' => QuestionTypesEnum::NUMBER
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
