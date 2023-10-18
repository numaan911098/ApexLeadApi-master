<?php

namespace App\Modules\ExternalCheckout;

use App\Modules\Base\BaseManager;
use Facades\App\Services\Util;
use App\ExternalCheckout;
use App\ExternalCheckoutLog;
use App\Plan;
use Illuminate\Http\Request;
use App\Enums\ErrorTypesEnum;
use App\Enums\MediaTypesEnum;
use DB;
use Log;
use Auth;
use Storage;
use App\Enums\ErrorTypesEnum as ErrorTypes;

class ExternalCheckoutManager extends BaseManager
{
    /**
     * ExternalCheckoutManager instance.
     *
     * @var ExternalCheckoutLog
     */
    private $externalCheckoutLogModel;

    /**
     * ExternalCheckout instance.
     *
     * @var ExternalCheckout
     */
    private $externalCheckoutModel;

    /**
     * Plan instance.
     *
     * @var Plan
     */
    private $planModel;

    /**
     * Constructor.
     *
     * @param ExternalCheckout $externalCheckoutModel
     */
    public function __construct(
        ExternalCheckout $externalCheckoutModel,
        Plan $planModel,
        ExternalCheckoutLog $externalCheckoutLogModel
    ) {
        $this->externalCheckoutModel = $externalCheckoutModel;
        $this->planModel = $planModel;
        $this->externalCheckoutLogModel = $externalCheckoutLogModel;
    }

    /**
     * Create External Checkout.
     *
     * @param array $data
     * @return array
     */
    public function store(array $data)
    {
        $data['ref_id'] = Util::uuid4();

        $this->addResponse('data', $this->externalCheckoutModel->create($data));

        return $this->response($data);
    }

    /**
     * Update External Checkout.
     *
     * @param ExternalCheckout $externalCheckout
     * @param array $data
     * @return array
     */
    public function update(ExternalCheckout $externalCheckout, array $data)
    {
        $externalCheckout->title     = $data['title'];
        $externalCheckout->description     = $data['description'];
        $externalCheckout->form_heading     = $data['form_heading'];
        $externalCheckout->plan_id     = $data['plan_id'];
        $externalCheckout->fields    = $data['fields'];
        $externalCheckout->login    = $data['login'];
        $externalCheckout->enable    = $data['enable'];
        $externalCheckout->redirect_url     = $data['redirect_url'];
        $externalCheckout->save();

        $this->addResponse('data', $externalCheckout);

        return $this->response();
    }

    public function index()
    {
        $checkoutList = $this->externalCheckoutModel->paginate();
        $allCheckout = $checkoutList->items();

        $pagination = $checkoutList->toArray();

        unset($pagination['data']);

        return $this->fillResponse([
            'data' => $allCheckout,
            'pagination' => $pagination
        ])->response();
    }

    public function show($id)
    {
        $externalCheckoutDetail = $this->externalCheckoutModel->where('id', $id)->first();
        $response = $externalCheckoutDetail->toArray();
        $response['plans'] = $this->planModel->where('id', $externalCheckoutDetail['plan_id'])->first()->toArray();
        return $this->fillResponse([
            'data' => $response
        ])->response();
    }

    public function externalcheckout($refId)
    {
        $checkout = $this->externalCheckoutModel->where('ref_id', $refId)->firstOrFail();

        $script = Storage::disk('www')->get('js/external-checkout.js');
        $script = str_replace('API_URL', route('external-checkout.checkouts', ['id' => $refId]), $script);
        $script = str_replace('REGISTER_URL', route('register'), $script);
        $script = str_replace('SCRIPTS_DOMAIN', route('checkoutlogs'), $script);
        $script = str_replace('LOGIN_URL', Util::config('leadgen.client_app_token_login_url'), $script);
        $script = str_replace('VENDOR_ID', Util::config('leadgen.paddle_vendor_id'), $script);
        return response($script)->header('Content-Type', 'application/javascript;charset=UTF-8');
    }

    public function checkouts($refId)
    {
        $externalCheckout = $this->externalCheckoutModel->where('ref_id', $refId)->firstOrFail();
        $output = [];
        $output = $externalCheckout;
        $output['plans'] = $this->planModel->where('id', $externalCheckout['plan_id'])->firstOrFail();

        return $this->fillResponse([
            'data' => $output
        ])->response();
    }

    public function checkoutlogs(array $request)
    {
        $this->addResponse('data', $this->externalCheckoutLogModel->create($request));

        return $this->response($request);
    }

    public function destroy(ExternalCheckout $externalCheckout)
    {
        $externalCheckout->delete();

        return $this->response();
    }
}
