<?php

use Illuminate\Database\Migrations\Migration;
use App\Enums\QuestionTypesEnum;
use Illuminate\Support\Facades\DB;

class AddRangeQuestionToFormQuestionTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('form_question_types')->insertOrIgnore([
            'type' => QuestionTypesEnum::RANGE
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
