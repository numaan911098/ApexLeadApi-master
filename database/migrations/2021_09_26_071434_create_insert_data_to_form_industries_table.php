<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateInsertDataToFormIndustriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('form_template_industries')->insertOrIgnore([
            [
                'title' => 'Automotive',
                'slug' => 'automotive'
            ],
            [
                'title' => 'B2B',
                'slug' => 'b2b'
            ],
            [
                'title' => 'E-Commerce',
                'slug' => 'ecommerce'
            ],
            [
                'title' => 'Education & Training',
                'slug' => 'education&training'
            ],
            [
                'title' => 'Finance',
                'slug' => 'finance'
            ],
            [
                'title' => 'Healthcare',
                'slug' => 'healthcare'
            ],
            [
                'title' => 'Marketing/ Digital Agencies',
                'slug' => 'marketing/digitalagencies'
            ],
            [
                'title' => 'Real Estate',
                'slug' => 'realestate'
            ],
            [
                'title' => 'Recruitment',
                'slug' => 'recruitment'
            ],
            [
                'title' => 'SaaS',
                'slug' => 'saas'
            ],
            [
                'title' => 'Travel',
                'slug' => 'travel'
            ],
            [
                'title' => 'Startups',
                'slug' => 'startups'
            ],
            [
                'title' => 'Web Design Agencies',
                'slug' => 'webdesignagencies'
            ],
            [
                'title' => 'Insurance',
                'slug' => 'insurance'
            ],
            [
                'title' => 'Debt',
                'slug' => 'debt'
            ],
            [
                'title' => 'Mortgage',
                'slug' => 'mortgage'
            ],
            [
                'title' => 'Utilities',
                'slug' => 'utilities'
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
        DB::table('form_template_industries')->delete();
    }
}
