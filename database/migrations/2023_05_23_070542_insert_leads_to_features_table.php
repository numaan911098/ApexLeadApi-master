<?php

use App\Enums\PackageBuilder\FeatureEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertLeadsToFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('features')->insertOrIgnore(
            [
                'title' => strtoupper(FeatureEnum::LEADS),
                'slug' => FeatureEnum::LEADS
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('features')->wher('slug', FeatureEnum::LEADS)->delete();
    }
}
