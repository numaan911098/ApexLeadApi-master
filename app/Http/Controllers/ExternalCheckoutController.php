<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Facades\App\Services\Util;
use App\Modules\ExternalCheckout\ExternalCheckoutManager;
use App\Http\Requests\StoreExternalCheckoutRequest;
use App\ExternalCheckout;
use App\Services\Lists\GeneralListService;
use Storage;
use Illuminate\Support\Facades\Log;
use DB;
use Auth;

class ExternalCheckoutController extends Controller
{
    /**
     * ExternalCheckoutManager instance.
     *
     * @var ExternalCheckoutManager
     */
    protected $externalCheckoutMgr;

    /**
     * ExternalCheckout domain URL.
     *
     * @var string
     */
    protected $externalCheckoutUrl;

    /**
     * Constructor.
     *
     * @param ExternalCheckoutManager $externalCheckoutMgr
     */

    private GeneralListService $generalListService;


    public function __construct(
        ExternalCheckoutManager $externalCheckoutMgr,
        GeneralListService $generalListService
    ) {
        $this->middleware('jwt.auth', ['except' => ['externalcheckout', 'checkouts', 'checkoutlogs']]);

        $this->externalCheckoutMgr = $externalCheckoutMgr;
        $this->externalCheckoutUrl = Util::config('leadgen.scripts_domain');
        $this->generalListService = $generalListService;
    }

    /**
     * Get list of External Checkout.
     *
     * @return Response
     */
    public function getExternalCheckoutLists(Request $request)
    {
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }

    public function index()
    {
        return $this->managerResponse($this->externalCheckoutMgr->index());
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->externalCheckoutMgr->show($id);
    }

    /**
     * Store External Checkout.
     *
     * @param StoreExternalCheckoutRequest $request
     * @return Response
     */
    public function store(StoreExternalCheckoutRequest $request)
    {
        return $this->managerResponse($this->externalCheckoutMgr->store($request->all()));
    }

    /**
     * Store External Checkout.
     *
     * @param StoreExternalCheckoutRequest $request
     * @return Response
     */
    public function update(StoreExternalCheckoutRequest $request, ExternalCheckout $externalCheckout)
    {
        return $this->managerResponse($this->externalCheckoutMgr->update($externalCheckout, $request->all()));
    }

    public function externalcheckout($refId)
    {
        return $this->externalCheckoutMgr->externalcheckout($refId);
    }

    public function checkouts($refId)
    {
        return $this->externalCheckoutMgr->checkouts($refId);
    }

    public function destroy(ExternalCheckout $externalCheckout)
    {
        return $this->externalCheckoutMgr->destroy($externalCheckout);
    }

    public function checkoutlogs(Request $request)
    {
        return $this->managerResponse($this->externalCheckoutMgr->checkoutlogs($request->all()));
    }
}
