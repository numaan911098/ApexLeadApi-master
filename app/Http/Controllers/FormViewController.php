<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Form;
use App\FormVariant;

class FormViewController extends Controller
{
    /**
     * Form Model.
     */
    private Form $formModel;

    /**
     * Form variant model.
     */
    private FormVariant $formVariantModel;

    public function __construct(Form $formModel, FormVariant $formVariantModel)
    {
        $this->formModel = $formModel;
        $this->formVariantModel = $formVariantModel;
    }

    /**
     * Generate Form UI for publish.
     *
     * @param string $key Form key.
     * @return string
     */
    public function publish($key, $method = null)
    {
        $form = Form::where('key', $key)->first();

        if (empty($form)) {
            die();
        }

        if ($method === 'iframe') {
            return view('forms.iframe', compact('form'));
        }

        $style = 'display:block;border:0;width:100%;max-width:600px;margin:0 auto;';

        return view('forms.publish', compact('form', 'style'));
    }

    /**
     * @param Form $form
     * @param FormVariant $variant
     * @return mixed
     */
    public function preview(Request $request, Form $form, FormVariant $variant)
    {
        $style = 'display:block;border:0;width:100%;max-width:600px;margin:0 auto;';

        return view('forms.preview', compact('form', 'style', 'variant'));
    }
}
