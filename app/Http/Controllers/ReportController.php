<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Lists\GeneralListService;
use App\Modules\Security\Services\AuthService;

class ReportController extends Controller
{
    /**
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * @var GeneralListService
     */
    protected $generalListService;

    /**
     * PackageBuilderController constructor.
     * @param AuthService $authService
     * @param GeneralListService $generalListService
     */
    public function __construct(
        AuthService $authService,
        GeneralListService $generalListService
    ) {
        $this->middleware('jwt.auth');
        $this->authService = $authService;
        $this->generalListService = $generalListService;
    }

    /**
     * Display a listing of the resource.
     * @param Request
     */
    public function getReportList(Request $request)
    {
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }
}
