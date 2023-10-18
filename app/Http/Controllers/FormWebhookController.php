<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FormWebhook;
use App\FormWebhookRequest;
use App\Form;
use App\FormVariant;
use App\Http\Requests\StoreFormWebhookRequest;
use Log;

class FormWebhookController extends Controller
{
    /**
     * @var FormWebhook
    */
    protected $formWebhookModel;

    /**
     * Constructor.
     *
     * @param FormWebook $formWebhook
     */

    public function __construct(FormWebhook $formWebhook)
    {
        $this->middleware('jwt.auth');
        $this->formWebhookModel = $formWebhook;
    }

    public function index(Form $form)
    {
        $this->authorize('view', $form);

        $webhooks = FormWebhook::where('form_id', $form->id)
            ->with('formVariant')
            ->latest()
            ->get();

        foreach ($webhooks as $webhook) {
            $webhook->form_webhook_requests_count = FormWebhookRequest::where('form_webhook_id', $webhook->id)->count();
        }

        return $this->apiResponse(200, $webhooks->toArray());
    }

    public function show(Form $form, FormWebhook $webhook)
    {
        $this->authorize('view', $form);

        return $this->apiResponse(200, $webhook->toArray());
    }

    public function store(StoreFormWebhookRequest $request, Form $form)
    {
        $this->authorize('view', $form);

        $variant = FormVariant::find($request->input('form_variant_id'));

        $data = $request->all();
        $data['form_id'] = $form->id;

        if (!empty($request->input('fields_map'))) {
            $data['fields_map'] = json_encode($data['fields_map']);
        }

        if (!empty($request->input('secret'))) {
            $data['secret'] = json_encode($data['secret']);
        }

        if (!empty($request->input('headers'))) {
            $data['headers'] = json_encode($data['headers']);
        }

        $webhook = FormWebhook::create($data);

        $webhook->form_variant = $variant;

        return $this->apiResponse(200, $webhook->toArray());
    }

    public function update(StoreFormWebhookRequest $request, Form $form, FormWebhook $webhook)
    {
        $this->authorize('view', $form);

        if ($request->input('from') === 'global') {
            $globalWebhook = $this->formWebhookModel->find($request->input('id'));
            $globalWebhook->update([
                'title' => $request->input('title'),
                'enable' => $request->input('enable'),
                'url' => $request->input('url'),
                'format' => $request->input('format'),
                'method' => $request->input('method')
            ]);
            return $this->apiResponse(200, $globalWebhook->toArray());
        }

        $webhook->title = $request->input('title');
        $webhook->enable = $request->input('enable');
        $webhook->url = $request->input('url');
        $webhook->format = $request->input('format');
        $webhook->method = $request->input('method');
        $webhook->secret = json_encode($request->input('secret'));
        $webhook->headers = json_encode($request->input('headers'));

        if (empty($request->input('form_variant_id'))) {
            $webhook->form_variant_id = null;
            $webhook->fields_map = null;
            $webhook->save();
        } else {
            $variant = FormVariant::findOrFail($request->input('form_variant_id'));
            $webhook->form_variant_id = $variant->id;
            if (!empty($request->input('fields_map'))) {
                $webhook->fields_map = json_encode($request->input('fields_map'));
            }
            $webhook->save();

            $webhook->form_variant = $variant;
        }

        return $this->apiResponse(200, $webhook->toArray());
    }

    public function destroy(Form $form, FormWebhook $webhook)
    {
        $this->authorize('view', $form);

        $webhook->delete();

        return $this->apiResponse(200);
    }
}
