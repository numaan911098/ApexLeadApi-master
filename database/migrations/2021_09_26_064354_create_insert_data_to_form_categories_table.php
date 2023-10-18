<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateInsertDataToFormCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('form_template_categories')->insertOrIgnore([
            [
                'title' => 'Application Forms',
                'slug' => 'applicationforms'
            ],
            [
                'title' => 'Briefing Forms',
                'slug' => 'briefingforms'
            ],
            [
                'title' => 'Calculator Form',
                'slug' => 'calculatorform'
            ],
            [
                'title' => 'Contact Forms',
                'slug' => 'contactforms'
            ],
            [
                'title' => 'Feedback Forms',
                'slug' => 'feedbackforms'
            ],
            [
                'title' => 'Lead Capture Forms',
                'slug' => 'leadcaptureforms'
            ],
            [
                'title' => 'Lead Qualification',
                'slug' => 'leadqualification'
            ],
            [
                'title' => 'Registration Forms',
                'slug' => 'registrationforms'
            ],
            [
                'title' => 'Surveys',
                'slug' => 'surveys'
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('form_template_categories')->delete();
    }
}
