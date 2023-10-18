<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Services\PackageBuilderService;
use App\Services\Lists\GeneralListService;
use App\Modules\Security\Services\AuthService;
use App\Enums\ErrorTypesEnum;
use Illuminate\Support\Facades\Validator;

class PackageBuilderController extends Controller
{
    /**
     * @var AuthService
     */
    private AuthService $authService;

    /**
     * @var GeneralListService
     */
    private $generalListService;

    /**
     * @var PackageBuilderService
     */
    private $packageBuilderService;

    /**
     * @var Package
     */
    private $packageModel;

    /**
     * PackageBuilderController constructor.
     * @param GeneralListService $generalListService
     */
    public function __construct(
        AuthService $authService,
        GeneralListService $generalListService,
        PackageBuilderService $packageBuilderService,
        Package $package
    ) {
        $this->middleware('jwt.auth');
        $this->authService = $authService;
        $this->generalListService = $generalListService;
        $this->packageBuilderService = $packageBuilderService;
        $this->packageModel = $package;
    }

    /**
     * Display a listing of the resource.
     * @param Request
     */
    public function getPackageBuilderList(Request $request)
    {
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $authUser = $this->authService->getUser();
        if (!$authUser->isAdmin()) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $validator = Validator::make($request->all(), [
            'packageTitle' => 'required|min:5|max:150',
            'packageDescription' => 'max:300',
            'packagePlan' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $packageId = $this->packageModel->where('plan_id', $request->input('packagePlan'))->first();
        if (!empty($packageId)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::TEMPLATE_ID_ALREADY_EXIST,
                'This plan package already exists.'
            );
        }
        $result =  $this->packageBuilderService->savePackageData($request->all());
        return $this->apiResponse(200, $result);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result =  $this->packageBuilderService->getPackageData($id);
        return $this->apiResponse(200, $result->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $authUser = $this->authService->getUser();
        if (!$authUser->isAdmin()) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypesEnum::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $validator = Validator::make($request->all(), [
            'packageTitle' => 'required|min:5|max:150',
            'packageDescription' => 'max:300',
            'packagePlan' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $result =  $this->packageBuilderService->updatePackageData($request->all(), (int) $id);
        return $this->apiResponse(200, $result);
    }
}
