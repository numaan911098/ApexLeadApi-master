<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\FormThemeTemplate;
use Facades\App\Services\Util;

class UpdateThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $themeDefaults = Util::themeDefault();
        $themes = config('formThemeTemplates');

        foreach ($themes as $theme) {
            $config = Util::arrayMergeRecursiveDistinct($themeDefaults, $theme['config']);
            $themeTitle = $theme['name'];
            $themeTemplate = FormThemeTemplate::where('title', $themeTitle)->first();

            if ($themeTemplate) {
                $themeTemplate->config = json_encode($config);
                $themeTemplate->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
