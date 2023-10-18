<?php

use Illuminate\Database\Migrations\Migration;
use App\Enums\PackageBuilder\FeatureEnum;
use Illuminate\Support\Facades\DB;

class InsertTrustedformToFeaturesTable extends Migration
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
                'title' => strtoupper(FeatureEnum::TRUSTEDFORM),
                'slug' => FeatureEnum::TRUSTEDFORM
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
