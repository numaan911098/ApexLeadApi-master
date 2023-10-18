<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreGoogleRecaptchaKey;
use App\GoogleRecaptchaKey;
use App\Enums\GoogleRecaptchaTypesEnum;
use App\Enums\ErrorTypesEnum;
use Auth;
use Log;

class GoogleRecaptchaKeyController extends Controller
{

    public function index()
    {
        $googleRecaptchaKeys = GoogleRecaptchaKey::where('created_by', Auth::id())
        ->get();

        foreach ($googleRecaptchaKeys as $index => $googleRecaptchaKey) {
            $googleRecaptchaKey['forms_count'] = $googleRecaptchaKey->formCount();
        }

        return $this->apiResponse(200, $googleRecaptchaKeys->toArray());
    }

    public function store(StoreGoogleRecaptchaKey $request)
    {
        $data = $request->all();
        $data['type'] = GoogleRecaptchaTypesEnum::V2_INVISIBLE;
        $data['created_by'] = Auth::id();

        $googleRecaptchaKey = GoogleRecaptchaKey::create($data);

        $googleRecaptchaKey['forms_count'] = $googleRecaptchaKey->formCount();

        return $this->apiResponse(200, $googleRecaptchaKey->toArray());
    }

    public function update(
        StoreGoogleRecaptchaKey $request,
        GoogleRecaptchaKey $googleRecaptchaKey
    ) {
        $this->authorize('update', $googleRecaptchaKey);

        $googleRecaptchaKey->title = $request->input('title');
        $googleRecaptchaKey->site_key = $request->input('site_key');
        $googleRecaptchaKey->secret_key = $request->input('secret_key');
        $googleRecaptchaKey->save();

        return $this->apiResponse(200, $googleRecaptchaKey->toArray());
    }

    public function destroy(GoogleRecaptchaKey $googleRecaptchaKey)
    {
        $this->authorize('update', $googleRecaptchaKey);

        if ($googleRecaptchaKey->formCount() > 0) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::GOOGLE_RECAPTCHA_IN_USE,
                'Google Recaptcha Key is already in use.'
            );
        }

        $googleRecaptchaKey->forceDelete();

        return $this->apiResponse(200);
    }
}
