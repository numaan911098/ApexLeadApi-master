<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Facades\App\Modules\Icon\Services\IconService;
use App\Enums\IconLibraryEnum;
use Facades\App\Services\Util;
use Storage;
use Log;

class FormVariantTheme extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'general',
        'typography',
        'ui_elements',
        'custom_css',
        'form_variant_id'
    ];

    public function getGeneralAttribute($value)
    {
        $themeDefaults = Util::themeDefault();

        $general = json_decode($value, true);

        $general = Util::arrayMergeRecursiveDistinct($themeDefaults['general'], $general);

        $this->castToBoolean($general['dynamic_height']);
        $this->castToBoolean($general['dynamic_fadein']);
        $this->castToBoolean($general['inherit_toggle']);

        return $general;
    }

    public function getTypographyAttribute($value)
    {
        $themeDefaults = Util::themeDefault();

        $typography = json_decode($value, true);

        $typography = Util::arrayMergeRecursiveDistinct($themeDefaults['typography'], $typography);

        return $typography;
    }

    public function setCustomCssAttribute($value)
    {
        $this->attributes['custom_css'] = empty($value) ? '' : $value;
    }

    public function getUiElementsAttribute($value)
    {
        $themeDefaults = Util::themeDefault();

        $uiElements = json_decode($value, true);

        $uiElements = Util::arrayMergeRecursiveDistinct($themeDefaults['ui_elements'], $uiElements);

        $this->castToBoolean($uiElements['question']['asterisk_for_required']);
        $this->castToBoolean($uiElements['question']['hide_question_labels']);

        $this->castToBoolean($uiElements['step_navigation']['back_button']['rounded']);
        $this->castToBoolean($uiElements['step_navigation']['back_button']['shadow']);
        $this->castToBoolean($uiElements['step_navigation']['back_button']['hide']);

        $this->castToBoolean($uiElements['step_navigation']['next_button']['rounded']);
        $this->castToBoolean($uiElements['step_navigation']['next_button']['shadow']);
        $this->castToBoolean($uiElements['step_navigation']['next_button']['hidden']);



        $this->castToBoolean($uiElements['step_navigation']['submit_button']['shadow']);

        $this->castToBoolean($uiElements['background']['formShadow']);
        $this->castToBoolean($uiElements['step_progress']['showProgress']);
        $this->castToBoolean($uiElements['step_progress']['showAnimation']);
        $this->castToBoolean($uiElements['choice']['image_icon_skin']['shadow']);
        $this->castToBoolean($uiElements['choice']['image_icon_skin']['hover_style']['shadow']);
        $this->castToBoolean($uiElements['choice']['image_icon_skin']['active_style']['shadow']);

        $this->castToInt($uiElements['step_navigation']['back_button']['borderRadius']);
        $this->castToInt($uiElements['step_navigation']['next_button']['borderRadius']);
        $this->castToInt($uiElements['step_navigation']['submit_button']['borderRadius']);

        return $uiElements;
    }

    public function getTheme()
    {
        return [
            'general' => $this->general,
            'typography' => $this->typography,
            'ui_elements' => $this->ui_elements,
            'custom_css' => $this->custom_css,
        ];
    }

    public function getThemeSvgIcons(array $theme = []): array
    {
        $icons = [];
        $prefix = 'material-icons ';

        if (empty($theme)) {
            $theme = $this->getTheme();
        }

        $stepNavigation = $theme['ui_elements']['step_navigation'];

        if (!empty($stepNavigation['back_button']['icon'])) {
            $icon  = $stepNavigation['back_button']['icon'];
            $icons[$prefix . $icon] = IconService::getSvgIcon($prefix . $icon);
        }

        if (!empty($stepNavigation['next_button']['icon'])) {
            $icon  = $stepNavigation['next_button']['icon'];
            $icons[$prefix . $icon] = IconService::getSvgIcon($prefix . $icon);
        }

        if (!empty($stepNavigation['submit_button']['icon'])) {
            $icon  = $stepNavigation['submit_button']['icon'];
            $icons[$prefix . $icon] = IconService::getSvgIcon($prefix . $icon);
        }

        return $icons;
    }

    public function castToBoolean(&$value)
    {
        if ($value === 'true' || $value === true) {
            $value = true;

            return;
        }

        $value = false;
    }

    public function castToInt(&$value)
    {
        if (is_numeric($value)) {
            $value = (int) $value;

            return;
        }

        $value = 0;
    }
}
