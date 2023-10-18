<?php

use App\Enums\PackageBuilder\FeatureEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertPartialLeadsToFeaturesTable extends Migration
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
                'title' => strtoupper(FeatureEnum::PARTIAL_LEADS),
                'slug' => FeatureEnum::PARTIAL_LEADS
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
        DB::table('features')->delete();
    }
}
