<?php

namespace App\Http\Controllers;

use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Http\Requests\StoreBlacklistIpRequest;
use App\Modules\Security\Services\AuthService;
use Facades\App\Services\Util;
use Illuminate\Http\Request;
use App\Models\BlacklistIp;

class BlacklistIpController extends Controller
{
    /**
     * @var BlacklistIp
     */
    private BlacklistIp $blacklistIp;

    /**
     * @var AuthService
     */
    private AuthService $authService;

    /**
     * BlacklistIpController constructor.
     * @param BlacklistIp $blacklistIp
     * @param AuthService $authService
     */
    public function __construct(
        BlacklistIp $blacklistIp,
        AuthService $authService
    ) {
        $this->middleware('jwt.auth');

        $this->blacklistIp = $blacklistIp;
        $this->authService = $authService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $blacklistIps = $this->blacklistIp->all()->toArray();

        return $this->apiResponse(200, $blacklistIps);
    }


    /**
     * @param StoreBlacklistIpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBlacklistIpRequest $request)
    {
        $authUser = $this->authService->getUser();
        if (!$authUser->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $data = [
            'ip' => $request->input('ip'),
            'reason' => $request->input('reason'),
            'operator' => $request->input('operator'),
        ];
        $blacklistIp = $this->blacklistIp->create($data);

        return $this->apiResponse(200, $blacklistIp->toArray());
    }

    /**
     * @param StoreBlacklistIpRequest $request
     * @param BlacklistIp $blacklistIp
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StoreBlacklistIpRequest $request, BlacklistIp $blacklistIp)
    {
        $authUser = $this->authService->getUser();
        if (!$authUser->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $blacklistIp->ip = trim($request->input('ip'));
        $blacklistIp->reason = $request->input('reason');
        $blacklistIp->operator = $request->input('operator');
        $blacklistIp->save();

        return $this->apiResponse(200, $blacklistIp->toArray());
    }

    /**
     * @param Request $request
     * @param BlacklistIp $blacklistIp
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request, BlacklistIp $blacklistIp)
    {
        $authUser = $this->authService->getUser();
        if (!$authUser->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $blacklistIp->delete();

        return $this->apiResponse(200);
    }
}
