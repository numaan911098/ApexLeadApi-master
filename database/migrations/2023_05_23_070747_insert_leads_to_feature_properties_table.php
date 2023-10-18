<?php

use App\Enums\PackageBuilder\FeatureEnum;
use App\Enums\PackageBuilder\FeaturePropertyEnum;
use App\Enums\PackageBuilder\FeaturePropertyTypeEnum;
use App\Enums\PackageBuilder\FeaturePropertyUnitEnum;
use App\Models\Feature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertLeadsToFeaturePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $leadsFeature = Feature::where('slug', FeatureEnum::LEADS)->first();
        if (!$leadsFeature) {
            return;
        }

        DB::table('feature_properties')->insertOrIgnore([
            'title' => FeaturePropertyEnum::NO_OF_LEADS,
            'type' => FeaturePropertyTypeEnum::NUMBER,
            'unit' => FeaturePropertyUnitEnum::UNIT_NO_OF_LEADS,
            'isResetPeriod' => true,
            'feature_id' => $leadsFeature->id
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('feature_properties')->wher('title', FeaturePropertyEnum::NO_OF_LEADS)->delete();
    }
}
