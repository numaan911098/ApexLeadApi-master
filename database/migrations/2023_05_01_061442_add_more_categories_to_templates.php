<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMoreCategoriesToTemplates extends Migration
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
                'title' => 'Subscription Forms',
                'slug' => 'subscription_forms'
            ],
            [
                'title' => 'Landing Page Forms',
                'slug' => 'landing_page_forms'
            ],
            [
                'title' => 'Data Collection Forms',
                'slug' => 'data_collection_forms'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $categoriesToDelete = array(
            'subscription_forms', 'landing_page_forms', 'data_collection_forms'

        );
        DB::table('form_template_categories')->whereIn('slug', $categoriesToDelete)->delete();
    }
}
