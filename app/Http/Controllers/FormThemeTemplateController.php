<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FormThemeTemplate;
use App\Http\Requests\StoreFormThemeTemplateRequest;
use App\Http\Requests\PublishFormThemeTemplate;
use App\Enums\{
FormThemeTemplateTypesEnum,
ErrorTypesEnum
};
use App\Modules\Security\Services\AuthService;
use Facades\App\Services\Util;
use Auth;
use Storage;
use Illuminate\Support\Facades\Log;

class FormThemeTemplateController extends Controller
{
    /**
     * AuthService instance.
     */
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->middleware('jwt.auth');

        $this->authService = $authService;
    }

    public function index()
    {
        $defaultTemplates = FormThemeTemplate::where('type', FormThemeTemplateTypesEnum::DEFAULT)
        ->with('themeImage')->latest()->get();
        $customTemplates = FormThemeTemplate::where('type', FormThemeTemplateTypesEnum::CUSTOM)
                            ->where('user_id', $this->authService->getUserId())
                            ->with('themeImage')
                            ->get();

        return $this->apiResponse(
            200,
            $defaultTemplates->merge($customTemplates)->toArray()
        );
    }

    public function getDefaultTemplate()
    {
        return $this->apiResponse(200, Util::themeDefault());
    }

    public function store(StoreFormThemeTemplateRequest $request)
    {
        $data = $request->all();

        $themeDefaults = Util::themeDefault();

        $data['config'] = json_encode(Util::arrayMergeRecursiveDistinct($themeDefaults, $data['config']));

        $data['user_id'] = $this->authService->getUserId();

        $data['type'] = FormThemeTemplateTypesEnum::CUSTOM;

        $data['media_id'] = $data['imageId'];

        $template = FormThemeTemplate::create($data);

        return $this->apiResponse(200, $template->toArray());
    }

    public function update(StoreFormThemeTemplateRequest $request, FormThemeTemplate $formThemeTemplate)
    {
        $authUser = $this->authService->getUser();

        if (!$authUser->isAdmin() && $formThemeTemplate->isDefault()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        if ($authUser->id !== $formThemeTemplate->user_id && $formThemeTemplate->isCustom()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $themeDefaults = Util::themeDefault();

        $formThemeTemplate->title = $request->input('title');
        $formThemeTemplate->media_id = $request->input('imageId');
        $formThemeTemplate->config = json_encode(
            Util::arrayMergeRecursiveDistinct($themeDefaults, $request->input('config'))
        );

        $formThemeTemplate->save();

        return $this->apiResponse(200, $formThemeTemplate->toArray());
    }

    public function publishTemplate(PublishFormThemeTemplate $request, FormThemeTemplate $formThemeTemplate)
    {
        if (!$this->authService->getUser()->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $formThemeTemplate->title = $request->input('title');
        $formThemeTemplate->type = FormThemeTemplateTypesEnum::DEFAULT;
        $formThemeTemplate->published = 1;
        $formThemeTemplate->save();

        return $this->apiResponse(200, $formThemeTemplate->toArray());
    }

    public function deactivateDefaultTemplate(FormThemeTemplate $formThemeTemplate)
    {
        if (!$this->authService->getUser()->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $formThemeTemplate->published = 0;
        $formThemeTemplate->save();

        return $this->apiResponse(200, $formThemeTemplate->toArray());
    }

    public function destroy(FormThemeTemplate $formThemeTemplate)
    {
        $authUser = $this->authService->getUser();

        if (!$authUser->isAdmin() && $formThemeTemplate->isDefault()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action.'
            );
        }

        if ($authUser->id !== $formThemeTemplate->user_id && $formThemeTemplate->isCustom()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $formThemeTemplate->delete();

        return $this->apiResponse(200);
    }
}
