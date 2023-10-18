<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FormThemeTemplateTypesEnum;
use Facades\App\Services\Util;
use Storage;
use Log;

class FormThemeTemplate extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'config',
        'user_id',
        'media_id'
    ];

    public function getConfigAttribute($value)
    {
        $value = json_decode($value, true);

        $themeDefaults = Util::themeDefault();

        $uiElements = Util::arrayMergeRecursiveDistinct($themeDefaults['ui_elements'], $value['ui_elements']);

        // casting
        $this->castToBoolean($uiElements['question']['asterisk_for_required']);
        $this->castToBoolean($uiElements['question']['hide_question_labels']);

        $this->castToBoolean($uiElements['step_navigation']['back_button']['rounded']);
        $this->castToBoolean($uiElements['step_navigation']['back_button']['shadow']);
        $this->castToBoolean($uiElements['step_navigation']['back_button']['hide']);

        $this->castToBoolean($uiElements['step_navigation']['next_button']['rounded']);
        $this->castToBoolean($uiElements['step_navigation']['next_button']['shadow']);

        $this->castToBoolean($uiElements['step_navigation']['submit_button']['shadow']);

        $this->castToBoolean($uiElements['background']['formShadow']);
        $this->castToBoolean($uiElements['step_progress']['showProgress']);

        $uiElements['step_navigation']['back_button']['borderRadius'] =
        (int) $uiElements['step_navigation']['back_button']['borderRadius'];

        $uiElements['step_navigation']['next_button']['borderRadius'] =
        (int) $uiElements['step_navigation']['next_button']['borderRadius'];

        $uiElements['step_navigation']['submit_button']['borderRadius'] =
        (int) $uiElements['step_navigation']['submit_button']['borderRadius'];

        $value['ui_elements'] = $uiElements;

        return $value;
    }

    public function castToBoolean(&$value)
    {
        if ($value === 'true' || $value === true) {
            $value = true;
        } else {
            $value = false;
        }
    }
    public function themeImage()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }

    /**
     * Check if template is default type.
     *
     * @return boolean
     */
    public function isDefault(): bool
    {
        return $this->type === FormThemeTemplateTypesEnum::DEFAULT;
    }

    /**
     * Check if template is custom type.
     *
     * @return boolean
     */
    public function isCustom(): bool
    {
        return $this->type === FormThemeTemplateTypesEnum::CUSTOM;
    }
}
