<?php

use App\Enums\PackageBuilder\FeatureEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertLeadProofToFeaturesTable extends Migration
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
                'title' => strtoupper(FeatureEnum::LEAD_PROOFS),
                'slug' => FeatureEnum::LEAD_PROOFS
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
