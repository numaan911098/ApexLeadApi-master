<?php

use App\Enums\PackageBuilder\FeatureEnum;
use App\Enums\PackageBuilder\FeaturePropertyEnum;
use App\Enums\PackageBuilder\FeaturePropertyTypeEnum;
use App\Enums\PackageBuilder\FeaturePropertyUnitEnum;
use App\Models\Feature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertPartialLeadsToFeaturePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $partialLeadsFeature = Feature::where('slug', FeatureEnum::PARTIAL_LEADS)->first();
        if (!$partialLeadsFeature) {
            return;
        }

        DB::table('feature_properties')->insertOrIgnore([
            'title' => FeaturePropertyEnum::NO_OF_PARTIAL_LEADS,
            'type' => FeaturePropertyTypeEnum::NUMBER,
            'unit' => FeaturePropertyUnitEnum::UNIT_NO_OF_PARTIAL_LEADS,
            'isResetPeriod' => true,
            'feature_id' => $partialLeadsFeature->id
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
