<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use App\Enums\LandingPageTemplateCodesEnum as TemplateCodes;
use App\Form;
use App\LandingPageTemplate;
use Auth;
use Log;

class StoreLandingPageTemplate1Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $hexColorRegex = "regex:/#([a-f0-9]{3}){1,2}\b/i";
        $rules = [
            'title' => 'required|regex:/^[a-zA-Z0-9 ]*$/',
            'description' => 'nullable|regex:/^[a-zA-Z0-9 ]*$/',
            'template.config' => 'required|array',
            'template.config.colors' => 'required|array',
            'template.config.cta' => 'required|array',
            'template.config.media_type' => 'required|array',
            'template.config.visibility' => 'required|array',
            'template.config.tracking' => 'required|array',

            'template.config.title' => 'nullable',
            'template.config.description' => 'nullable',

            'template.config.colors.body_bg.value' => 'required|' . $hexColorRegex,
            'template.config.colors.cta1_bg.value' => 'required|' . $hexColorRegex,
            'template.config.colors.cta1_color.value' => 'required|' . $hexColorRegex,

            'template.config.cta.cta_text' => 'required|regex:/^[a-zA-Z0-9 ]*$/',
            'template.config.cta.url' => 'url|nullable',
            'template.config.cta.cta_size' => 'required',
            'template.config.cta.cta_fullwidth' => 'boolean|required',

            'template.config.media_type.image_url' => 'url|nullable',
            'template.config.media_type.video_url' => 'url|nullable',
            'template.config.media_type.is_youtube_video' => 'boolean',

            'template.config.visibility.show_cta1.value' => 'required|boolean',
            'template.config.visibility.show_description.value' => 'required|boolean',
            'template.config.visibility.show_headline.value' => 'required|boolean',
            'template.config.visibility.show_media.value' => 'required|boolean',

            'template.config.tracking.enable' => 'required|boolean',
            'template.config.tracking.scripts.*.async' => 'boolean',
            'template.config.tracking.scripts.*.order' => 'integer',
            'template.config.tracking.scripts.*.url' => 'url|nullable'
        ];

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Util::apiResponse(
            422,
            [],
            ErrorType::INVALID_DATA,
            'Invalid data submitted',
            $validator->errors()->toArray()
        ));
    }

    public function withValidator($validator)
    {
        $template = $this->input('template');
        $validator->after(function ($validator) use ($template) {
            $this->validateCTA($validator, $template);
            $this->validateMedia($validator, $template);
            $this->validateTracking($validator, $template);
        });
    }

    protected function validateCTA(Validator $validator, array $template)
    {
        $visibility = $template['config']['visibility'];
        $cta = $template['config']['cta'];
        if ($visibility['show_cta1']['value']) {
            if (empty($cta['url']) && empty($cta['leadgen_form_id'])) {
                $validator->errors()->add(
                    'template.config.cta.(url|leadgen_form_id)',
                    'url or leadgen_Form_id must be set'
                );
            }
            if (!empty($cta['leadgen_form_id'])) {
                $forms = Form::where([
                    'key' => $cta['leadgen_form_id'],
                    'created_by' => Auth::id()
                ])->get();
                if (count($forms) === 0) {
                    $validator->errors()->add(
                        'template.config.leadgen_form_id',
                        'No record found for this id or form key'
                    );
                }
            }
        }
    }

    protected function validateMedia(Validator $validator, array $template)
    {
        $tpl = LandingPageTemplate::where('code', TemplateCodes::TPL1)->first();
        $mediaTypes = $tpl->config['media_type']['meta_readonly']['types'];
        $mediaSources = $tpl->config['media_type']['meta_readonly']['sources'];
        $mediaPositions = $tpl->config['media_type']['meta_readonly']['positions'];

        $visibility = $template['config']['visibility'];
        $media = $template['config']['media_type'];

        if ($visibility['show_media']['value']) {
            if (empty($media['type'])) {
                $validator->errors()->add(
                    'template.config.media_type.type',
                    'Field is required'
                );
            } else {
                if (!in_array($media['type'], $mediaTypes)) {
                    $validator->errors()->add(
                        'template.config.media_type.type',
                        'type value should be ' . implode(', ', $mediaTypes)
                    );
                }
            }

            if (empty($media['source'])) {
                $validator->errors()->add(
                    'template.config.media_type.source',
                    'Field is required'
                );
            } else {
                if (!in_array($media['source'], $mediaSources)) {
                    $validator->errors()->add(
                        'template.config.media_type.source',
                        'source value should be ' . implode(', ', $mediaSources)
                    );
                }
            }

            if (empty($media['position'])) {
                $validator->errors()->add(
                    'template.config.media_type.position',
                    'Field is required'
                );
            } else {
                $invalidPosition = true;
                foreach ($mediaPositions as $mediaPosition) {
                    if ($mediaPosition['value'] === $media['position']) {
                        $invalidPosition = false;
                        break;
                    }
                }
                if ($invalidPosition) {
                    $validator->errors()->add(
                        'template.config.media_type.position',
                        'type value should be ' . $this->nestedArrayToString($mediaPositions)
                    );
                }
            }
        }
    }

    protected function validateTracking(Validator $validator, array $template)
    {
        $tpl = LandingPageTemplate::where('code', TemplateCodes::TPL1)->first();
        $scriptTypes = $tpl->config['tracking']['meta_readonly']['script_types'];
        $scriptPositions = $tpl->config['tracking']['meta_readonly']['positions'];

        $visibility = $template['config']['visibility'];
        $tracking = $template['config']['tracking'];

        if ($tracking['enable']) {
            foreach ($tracking['scripts'] as $index => $script) {
                if (empty($script['position'])) {
                    $validator->errors()->add(
                        'template.config.tracking.scripts. ' . $index . ' .position',
                        'Field is required'
                    );
                } else {
                    $invalidScriptPosition = true;
                    foreach ($scriptPositions as $scriptPosition) {
                        if ($scriptPosition['value'] === $script['position']) {
                            $invalidScriptPosition = false;
                            break;
                        }
                    }
                    if ($invalidScriptPosition) {
                        $validator->errors()->add(
                            'template.config.tracking.scripts.' . $index . '.position',
                            'position value should be ' . $this->nestedArrayToString($scriptPositions)
                        );
                    }
                }

                if (empty($script['tag'])) {
                    $validator->errors()->add(
                        'template.config.tracking.scripts. ' . $index . ' .position',
                        'Field is required'
                    );
                } else {
                    $invalidScriptType = true;
                    foreach ($scriptTypes as $scriptType) {
                        if ($scriptType['value'] === $script['tag']) {
                            $invalidScriptType = false;
                            break;
                        }
                    }
                    if ($invalidScriptType) {
                        $validator->errors()->add(
                            'template.config.tracking.scripts.' . $index . '.tag',
                            'tag value should be ' . $this->nestedArrayToString($scriptTypes)
                        );
                    }
                }

                if (empty($script['order'])) {
                    $validator->errors()->add(
                        'template.config.tracking.scripts. ' . $index . ' .order',
                        'Field is required'
                    );
                }

                if ($script['tag'] === 'script_url') {
                    if (empty($script['async'])) {
                        $validator->errors()->add(
                            'template.config.tracking.scripts. ' . $index . ' .async',
                            'Field is required'
                        );
                    }
                    if (empty($script['url'])) {
                        $validator->errors()->add(
                            'template.config.tracking.scripts. ' . $index . ' .url',
                            'Field is required'
                        );
                    }
                }
            }
        }
    }

    protected function nestedArrayToString(array $nestedArray)
    {
        $str = '';
        $strArray = [];
        foreach ($nestedArray as $nestedItem) {
            $strArray[] = $nestedItem['value'];
        }
        return implode(', ', $strArray);
    }
}
