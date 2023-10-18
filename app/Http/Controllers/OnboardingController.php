<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Services\User\UserService;
use App\Onboarding;
use Validator;
use Auth;

class OnboardingController extends Controller
{
    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * OnboardingController constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->middleware('jwt.auth');
        $this->userService = $userService;
    }

    /**
     * Save onboarding state.
     *
     * @return void
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'required',
            'complete' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Invalid data submitted',
                $validator->errors()->toArray()
            );
        }

        $user       = Auth::user();
        $data       = $request->all();
        $onboarding = $user->onboarding;

        if (!empty($onboarding)) {
            $onboarding->page     = $data['page'];
            $onboarding->complete = $onboarding->complete === 1 ? 1 : $data['complete'];
            $onboarding->save();

            return $this->apiResponse();
        }

        Onboarding::create([
            'user_id' => $user->id,
            'page' => $data['page'],
            'complete' => $data['complete'],
        ]);

        return $this->apiResponse();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'industry' => 'required',
            'role' => 'required',
            'goal' => 'required',
            'first_heard' => 'nullable|string|min:5|max:50',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Invalid data submitted',
                $validator->errors()->toArray()
            );
        }

        $result = $this->userService->saveOnboardingData($request->all());
        return $this->apiResponse(200, $result);
    }
}
