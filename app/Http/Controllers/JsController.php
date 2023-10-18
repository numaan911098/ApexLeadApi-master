<?php

namespace App\Http\Controllers;

use App\Enums\DisksEnum;
use Illuminate\Http\Request;
use Facades\App\Services\Util;
use View;
use App\Form;
use App\User;
use App\Models\ContactState;
use Storage;
use Log;

class JsController extends Controller
{
    /**
     * @var string LeadGen API URL.
     */
    protected string $apiUrl;

    /**
     * @var string LeadGen API Path
     */
    protected string $apiPath;

    /**
     * @var string Form Domain URL.
     */
    protected string $formsUrl;

    /**
     * @var Form Form model instance.
     */
    protected Form $formModel;

    /**
     * @var User User model instance.
     */
    protected User $userModel;

    /**
     * @var ContactState ContactState model instance.
     */
    protected ContactState $contactStateModel;

    /**
     * JsController constructor.
     * @param Form $formModel
     * @param User $userModel
     * @param ContactState $contactStateModel
     */
    public function __construct(Form $formModel, User $userModel, ContactState $contactState)
    {
        $this->apiUrl = Util::config('leadgen.api_url');
        $this->apiPath = $this->apiUrl . '/api';
        $this->formsUrl = Util::config('leadgen.forms_domain');
        $this->formModel = $formModel;
        $this->userModel = $userModel;
        $this->contactStateModel = $contactState;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function leadgenFormByKey(string $key)
    {
        $form = $this->formModel->where('key', $key)->firstOrFail();
        $pageId = $this->contactStateModel->where('form_id', $form->id)->where('enable', true)->first();
        $user = $this->userModel->find($form->created_by);

        if ($form->isBlocked($user)) {
            return response('')->header('Content-Type', 'application/javascript;charset=UTF-8');
        }

        $script = Storage::disk(DisksEnum::RESOURCES)->get('/leadgenform/js/leadgen-key.js');
        $script = str_replace('LEADGEN_FORM_KEY', $form->key, $script);
        $script = str_replace('FORMS_URL', $this->formsUrl, $script);

        if ($pageId) {
            $script = str_replace('LANDING_PAGE_ID', $pageId->landingpage_id, $script);
            $script = str_replace('CSCERTIFY_SCRIPT', true, $script);
        } else {
            $script = str_replace('CSCERTIFY_SCRIPT', '', $script);
        }

        if (!empty($form->formSetting->tracking_ga4_property)) {
            $script = str_replace(
                'GA4_SCRIPT',
                sprintf('https://www.googletagmanager.com/gtag/js?id=%s', $form->formSetting->tracking_ga4_property),
                $script
            );
        } else {
            $script = str_replace(
                'GA4_SCRIPT',
                '',
                $script
            );
        }

        if ($form->formSetting->enable_google_recaptcha) {
            $script = str_replace('RECAPTCHA_SCRIPT', 'https://www.google.com/recaptcha/api.js', $script);
        } else {
            $script = str_replace('RECAPTCHA_SCRIPT', '', $script);
        }

        return response($script)->header('Content-Type', 'application/javascript;charset=UTF-8');
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function leadgenFormLibJsByKey(string $key)
    {
        $form = $this->formModel->where('key', $key)->firstOrFail();

        $script = $this->parseFormScriptVars($form, file_get_contents(public_path() . '/js/leadgenform-key.js'), true);

        return response($script)->header('Content-Type', 'application/javascript;charset=UTF-8');
    }

    /**
     * @return mixed
     */
    public function iframeHandler()
    {
        $script = Storage::disk(DisksEnum::RESOURCES)->get('/leadgenform/js/iframe/handler.js');

        return response($script)->header('Content-Type', 'text/javascript;charset=UTF-8');
    }

    /**
     * @param Form $form
     * @param string $script
     * @param bool $byKey
     * @return string
     */
    private function parseFormScriptVars(Form $form, string $script, bool $byKey = false): string
    {
        $script = str_replace(
            'API_URL',
            $this->apiPath,
            $script
        );

        if ($byKey) {
            $script = str_replace('LEADGEN_FORM_TAG', 'leadgen-form-' . $form->key, $script);
        } else {
            $script = str_replace('LEADGEN_FORM_TAG', 'leadgen-form-' . $form->id, $script);
        }

        $script = str_replace('LEADGEN_FORM_KEY', $form->key, $script);
        $script = str_replace('LEADGEN_FORM_ID', $form->id, $script);
        $form->formSetting = $form->formSetting;

        $script = str_replace(
            'LEADGEN_FORM_RECAPTCHA_SITEKEY',
            $form->getRecaptchaSiteKey(),
            $script
        );

        return $script;
    }
}
