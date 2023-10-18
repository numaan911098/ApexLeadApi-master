<?php

use App\Enums\PackageBuilder\FeatureEnum;
use App\Enums\PackageBuilder\FeaturePropertyEnum;
use App\Enums\PackageBuilder\FeaturePropertyTypeEnum;
use App\Enums\PackageBuilder\FeaturePropertyUnitEnum;
use App\Models\Feature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertTrustedformToFeaturePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $trustedFormFeature = Feature::where('slug', FeatureEnum::TRUSTEDFORM)->first();
        if (!$trustedFormFeature) {
            return;
        }

        DB::table('feature_properties')->insertOrIgnore([
            'title' => FeaturePropertyEnum::ENABLE_TRUSTEDFORM,
            'type' => FeaturePropertyTypeEnum::BOOLEAN,
            'unit' => FeaturePropertyUnitEnum::UNIT_BOOLEAN,
            'isResetPeriod' => false,
            'feature_id' => $trustedFormFeature->id
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('feature_properties')->delete();
    }
}
