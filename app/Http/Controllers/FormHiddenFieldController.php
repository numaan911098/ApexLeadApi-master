<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FormHiddenField;
use App\FormVariant;
use App\Form;
use App\Http\Requests\StoreFormHiddenFieldsRequest;
use Log;
use App\Events\FormVariantUpdated;

class FormHiddenFieldController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth')->except(['index']);
    }

    public function index(Form $form, FormVariant $variant)
    {
        $hiddenFields = FormHiddenField::where('form_id', $form->id)
            ->where('form_variant_id', $variant->id)
            ->get();

        return $this->apiResponse(200, $hiddenFields->toArray());
    }

    public function store(
        StoreFormHiddenFieldsRequest $request,
        Form $form,
        FormVariant $variant
    ) {
        $this->authorize('view', $form);

        if (empty($request->input('hiddenFields'))) {
            $hiddenFields = [];
        } else {
            $hiddenFields = $request->input('hiddenFields');
        }

        $newHiddenFields = [];

        foreach ($hiddenFields as $hiddenField) {
            if (
                $hiddenField['form_id'] != $form->id ||
                $hiddenField['form_variant_id'] != $variant->id
            ) {
                continue;
            }

            $formHiddenField = FormHiddenField::find($hiddenField['id']);

            if (!empty($formHiddenField)) {
                $formHiddenField->name = $hiddenField['name'];
                $formHiddenField->capture_from_url_parameter = $hiddenField['capture_from_url_parameter'];
                $formHiddenField->default_value = $hiddenField['default_value'];
                $formHiddenField->save();
            } else {
                $hiddenField['form_id'] = $form->id;
                $hiddenField['form_variant_id'] = $variant->id;
                $formHiddenField = FormHiddenField::create($hiddenField);
            }

            $newHiddenFields[] = $formHiddenField->id;
        }

        $formHiddenFields = FormHiddenField::where('form_id', $form->id)
            ->where('form_variant_id', $variant->id)
            ->get();

        foreach ($formHiddenFields as $formHiddenField) {
            if (!in_array($formHiddenField->id, $newHiddenFields)) {
                $formHiddenField->delete();
            }
        }

        event(new FormVariantUpdated($variant, $form));

        return $this->apiResponse(200, $variant->formHiddenFields->toArray());
    }
}
