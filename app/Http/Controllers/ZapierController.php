<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Form;
use App\FormVariant;
use Auth;
use Log;

class ZapierController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function forms()
    {
        $user = Auth::user();
        $forms = $user->forms;
        $variants = [];

        foreach ($forms as $form) {
            $variantsCount = $form->formVariants->count();

            foreach ($form->formVariants as $variant) {
                if ($variantsCount === 1) {
                    $variants[] = [
                        'id' => $variant->id,
                        'title' => 'Form (' . $form->title . ')',
                    ];

                    break;
                }

                $variants[] = [
                    'id' => $variant->id,
                    'title' =>  sprintf('Form (%s) -- Variant (%s)', $form->title, $variant->title),
                ];
            }
        }

        $variants = array_reverse($variants);

        return response()->json($variants);
    }

    public function formResponse(Request $request)
    {
        $user = Auth::user();

        $variant = FormVariant::find($request->query('form_variant_id'));

        if (empty($variant)) {
            return [];
        }

        if ($variant->form->created_by !== $user->id) {
            return [];
        }

        $leads = $variant
            ->formLeads()
            ->where('created_at', '>=', Carbon::yesterday()->toDateString())
            ->latest()
            ->get();

        if ($leads->count() === 0) {
            return [];
        }

        $responses = [];

        foreach ($leads as $lead) {
            $response = [
                'id' => $lead->id,
                'meta_reference_no' => $lead->reference_no,
                'meta_device_type' => $lead->formVisit->device_type,
                'meta_os' => $lead->formVisit->os,
                'meta_browser' => $lead->formVisit->browser,
                'meta_source_url' => $lead->formVisit->source_url,
                'meta_ip' => $lead->formVisit->ip,
                'meta_created_at' => $lead->created_at->toIso8601ZuluString(),
            ];

            if (is_numeric($lead->calculator_total)) {
                $response['meta_calculator_total'] = $lead->calculator_total;
                if (!empty($lead->formVariant->calculator_field_name)) {
                    $response[$lead->formVariant->calculator_field_name] = $lead->calculator_total;
                }
            }

            foreach ($lead->questionResponses as $questionResponse) {
                $key            = $questionResponse->formQuestion->name();
                $value          = $questionResponse->responseString($questionResponse);
                $response[$key] = $value;
            }

            foreach ($lead->hiddenFieldResponses as $hiddenFieldResponse) {
                $key            = $hiddenFieldResponse->formHiddenField->name;
                $value          = $hiddenFieldResponse->response;
                $response[$key] = empty($value) ? '' : $value;
            }

            $responses[] = $response;
        }

        return response()->json($responses);
    }
}
