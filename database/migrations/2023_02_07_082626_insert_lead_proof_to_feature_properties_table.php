<?php

use App\Enums\PackageBuilder\FeatureEnum;
use App\Enums\PackageBuilder\FeaturePropertyEnum;
use App\Enums\PackageBuilder\FeaturePropertyTypeEnum;
use App\Enums\PackageBuilder\FeaturePropertyUnitEnum;
use App\Models\Feature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertLeadProofToFeaturePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $leadProofsFeature = Feature::where('slug', FeatureEnum::LEAD_PROOFS)->first();
        if (!$leadProofsFeature) {
            return;
        }

        DB::table('feature_properties')->insertOrIgnore([
            'title' => FeaturePropertyEnum::NO_OF_LEAD_PROOFS,
            'type' => FeaturePropertyTypeEnum::NUMBER,
            'unit' => FeaturePropertyUnitEnum::UNIT_NO_OF_LEAD_PROOFS,
            'isResetPeriod' => true,
            'feature_id' => $leadProofsFeature->id
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
