<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Factory as Socialite;
use App\Services\SocialAuthService;
use App\Enums\ErrorTypesEnum;
use App\Events\UserLoggedIn;
use App\Services\BlacklistIpService;
use Sentry;
use Exception;

class SocialAuthController extends Controller
{
    /**
     * @var Socialite
     */
    private $socialite;

    /**
     * @var SocialAuthService
     */
    private $socialAuthService;

    /**
     * @var BlacklistIpService
     */
    private BlacklistIpService $blacklistIpService;

    /**
     * SocialAuthController constructor.
     * @param Socialite $socialite
     * @param SocialAuthService $socialAuthService
     * @param BlacklistIpService $blacklistIpService
     */
    public function __construct(
        Socialite $socialite,
        SocialAuthService $socialAuthService,
        BlacklistIpService $blacklistIpService
    ) {
        $this->socialite = $socialite;
        $this->socialAuthService = $socialAuthService;
        $this->blacklistIpService = $blacklistIpService;
    }

    /**
     * Redirect the user to the social network authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {
        try {
            return response()
                ->json(
                    [
                        'redirectUrl' => $this->socialite->driver($provider)
                            ->stateless()
                            ->with(["prompt" => "select_account"])
                            ->redirect()
                            ->getTargetUrl()
                    ]
                );
        } catch (Exception $exception) {
            Sentry\captureException($exception);
        }
    }

    /**
     * Handle callback
     *
     * @return \Illuminate\Http\Response
     */
    public function handleCallbackFromProvider($provider)
    {
        try {
            $user = $this->socialite->driver($provider)->stateless()->user();
            if (empty($user->email)) {
                $data = $this->apiResponse(
                    400,
                    [],
                    ErrorTypesEnum::INVALID_DATA,
                    'No email id returned from provider.'
                );
                return view('callback')->with('data', json_encode($data, true));
            }

            $socialUser = [
                'provider' => $provider,
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'google_user_id' => $user->getId(),
                'access_token' => $user->token,
                'expires_in' => $user->expiresIn
            ];

            $data = $this->apiResponse(
                201,
                $socialUser
            );
            return view('callback')->with('data', json_encode($data, true));
        } catch (Exception $exception) {
            Sentry\captureException($exception);
        }
    }

    /**
     * Register user
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        if (
            $this->blacklistIpService->isIpBlocked($request->ip())
        ) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::BLACKLISTED_IP,
                'Your ip is blockecd',
            );
        }

        $checkIfExists = $this->socialAuthService->checkIfExists($request->email);
        if (!empty($checkIfExists)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::EMAIL_ALREADY_EXIST,
                'The email has already been taken.'
            );
        }

        $addToProvider = $this->socialAuthService->addToProvider($request->all());
        $addToProvider->globalPartialLeadSetting = $addToProvider->globalPartialLeadSetting;
        if (!empty($addToProvider)) {
            return $this->apiResponse(
                201,
                array_merge(
                    $addToProvider->toArray(),
                    [
                        'token' => auth()->login($addToProvider),
                    ]
                )
            );
        } else {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::INVALID_DATA,
                'Unable to create user, something went wrong.'
            );
        }
    }

    /**
     * Authenticate user
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $checkUser = $this->socialAuthService->checkIfExists($request->email);
        if (empty($checkUser)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::NON_EXISTENCE_EMAIL,
                'Please provide the correct email address.'
            );
        }

        if (!$checkUser->active) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::SUSPENDED_ACCOUNT,
                'Your account has been suspended, please contact our support team.'
            );
        }

        if (
            ($checkUser->isCustomer()) && (is_null($checkUser->default_plan_id))
        ) {
            return $this->apiResponse(
                400,
                [
                    'user' => $checkUser->toArray(),
                    'signUpPlan' => $checkUser->signUpPlan
                ],
                ErrorTypesEnum::SIGNUP_INCOMPLETE,
                'You need to complete your account set-up first. Go here:'
            );
        }

        event(new UserLoggedIn($checkUser));

        $checkUser->defaultPlan = $checkUser->defaultPlan;
        $checkUser->globalPartialLeadSetting = $checkUser->globalPartialLeadSetting;
        $checkUser->plan = $checkUser->plan();
        $checkUser->show_onboarding = $checkUser->showOnboarding();
        $checkUser->load('onboarding');
        $checkUser->load('roles');

        return $this->apiResponse(
            200,
            [
                'token' => auth()->login($checkUser),
                'user' => $checkUser->toArray()
            ]
        );
    }
}
