<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFormVariantTheme;
use App\FormVariantTheme;
use App\FormVariant;
use App\Form;
use Log;

class FormVariantThemeController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth')->except(['show']);
    }

    public function show(Form $form, FormVariant $variant)
    {
        return $this->apiResponse(200, $variant->formVariantTheme->toArray());
    }

    public function store(StoreFormVariantTheme $request, Form $form, FormVariant $variant)
    {
        $this->authorize('view', $form);
        $data = $request->all();
        $data['form_variant_id'] = $variant->id;

        $newData = [];
        foreach ($data as $fieldName => $fieldValue) {
            if (gettype($fieldValue) === 'array') {
                $newData[$fieldName] = json_encode($fieldValue);
            } else {
                $newData[$fieldName] = $fieldValue;
            }
        }

        $formVariantTheme = FormVariantTheme::create($newData);

        return $this->apiResponse(200, $formVariantTheme->toArray());
    }

    public function update(StoreFormVariantTheme $request, Form $form, FormVariant $variant)
    {
        $this->authorize('view', $form);

        $formVariantTheme = $variant->formVariantTheme;

        $formVariantTheme->general = json_encode($request->input('general'));
        $formVariantTheme->typography = json_encode($request->input('typography'));
        $formVariantTheme->ui_elements = json_encode($request->input('ui_elements'));
        $formVariantTheme->custom_css = $request->input('custom_css');
        $formVariantTheme->save();

        return $this->apiResponse(200, $formVariantTheme->toArray());
    }
}
