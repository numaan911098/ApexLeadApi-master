<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFormVisitByKeyRequest;
use Facades\App\Services\Util;
use App\FormVisit;
use App\Form;
use App\Visitor;
use App\FormVariant;
use Agent;
use Log;
use Carbon\Carbon;

class FormVisitController extends Controller
{

    protected $formModel;

    protected $formVisitModel;

    protected $visitorModel;

    protected $formVariantModel;

    public function __construct(
        FormVisit $formVisitModel,
        Form $formModel,
        Visitor $visitor,
        FormVariant $formVariant
    ) {
        $this->formModel = $formModel;

        $this->formVisitModel = $formVisitModel;

        $this->visitorModel = $visitor;

        $this->formVariantModel = $formVariant;
    }

    public function storeByKey(StoreFormVisitByKeyRequest $request, $key)
    {
        $form = $this->formModel->where('key', $key)->firstOrFail();

        if ($form->current_experiment_id) {
            $formVariant = $form->variants()
            ->where('id', $request->input('variantId'))
            ->firstOrFail();
        } else {
            $formVariant = $form->championVariant();
        }

        if ($request->filled('visitorId')) {
            $visitor = $this->visitorModel
            ->where('ref_id', $request->input('visitorId'))
            ->firstOrFail();
        } else {
            $visitor = $this->visitorModel->create([
                'ref_id' => Util::uuid4()
            ]);
        }

        $geolocation = Util::geolocation($request->ip());

        $visit = $this->formVisitModel->createFromParams(
            $request,
            $form,
            $formVariant,
            $visitor,
            $geolocation
        );

        $visit->load('visitor');

        $data = $visit->toArray();

        return $this->apiResponse(200, $data);
    }

    public function updateInteractionTime(Request $request, $key, $visit_id)
    {
        $visitor = $this->formVisitModel
            ->where('id', $visit_id)
            ->firstOrFail();

        if ($visitor) {
            $visitor->interacted_at = Carbon::now()->toDateTimeString();

            $visitor->save();
        }

        return $this->apiResponse(200, $visitor->toArray());
    }
}
