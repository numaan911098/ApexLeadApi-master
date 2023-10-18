<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\RequestSourceEnum;
use App\Events\UserLoggedIn;
use App\User;
use Auth;
use Validator;
use App\Services\TwoFactorAuthService;
use App\Plan;
use Illuminate\Support\Facades\Log;

class AuthenticateController extends Controller
{
    use ThrottlesLogins;

    protected $decayMinutes = 10;

    /**
     * @var User
     */
    protected $userModel;

    /**
     * @var TwoFactorAuthService
     */
    private TwoFactorAuthService $twoFactorAuthService;

    /**
     * AuthenticateController constructor.
     * @param User $user
     * @param TwoFactorAuthService $twoFactorAuthService
     */
    public function __construct(User $user, TwoFactorAuthService $twoFactorAuthService)
    {
        $this->middleware('jwt.auth')->except(['authenticate']);
        $this->userModel = $user;
        $this->twoFactorAuthService = $twoFactorAuthService;
    }

    /**
     * Authenticate user
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required'
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

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $availableIn = round($this->limiter()->availableIn($this->throttleKey($request)) / 60, 0);

            return $this->apiResponse(
                400,
                [],
                ErrorTypes::TOO_MANY_ATTEMPTS,
                'Your account is locked due to too many attempts. Please wait for ' . $availableIn . ' minutes.'
            );
        }

        if (!empty($request->input('source')) && $request->input('source') === 'zapier') {
            $this->guard()->setTTL(null);
        }

        $token = $this->guard()->attempt($credentials);
        $user = $this->userModel->where('email', $credentials['email'])->first();

        $this->incrementLoginAttempts($request);

        if (empty($user)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::NON_EXISTENCE_EMAIL,
                'Please provide the correct email address.'
            );
        }

        if ($user->isOneToolUser()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::ONETOOL_USER_LOGIN,
                'OneTool user\'s should login from the OneTool Dashboard.'
            );
        }

        if (!$user->active) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::SUSPENDED_ACCOUNT,
                'Your account has been suspended, please contact our support team.'
            );
        }

        if (
            ($user->isCustomer()) && (is_null($user->default_plan_id))
        ) {
            return $this->apiResponse(
                400,
                [
                    'user' => $user->toArray(),
                    'signUpPlan' => $user->signUpPlan
                ],
                ErrorTypes::SIGNUP_INCOMPLETE,
                'You need to complete your account set-up first. Go here:'
            );
        }

        if ($user->isEmailVerificationNeeded()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::UNVERIFIED_ACCOUNT,
                'Your account is unverified. Please verify your account'
            );
        }
        $deviceId = $request->headers->get('User-Agent');

        if (!empty($token)) {
            if ($request->has('authenticateCode')) {
                $twoFactor = $request->only('authenticateCode', 'isTrustedDevice');
                $validator = Validator::make($twoFactor, [
                    'authenticateCode' => 'required|digits:6'
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

                $checkIfVerified = $this->twoFactorAuthService->verifyTwoFactor($user->id, $deviceId, $twoFactor);
                if (empty($checkIfVerified)) {
                    return $this->apiResponse(
                        400,
                        [],
                        ErrorTypes::INVALID_TWO_FACTOR_CODE,
                        'Invalid code entered'
                    );
                }
            } elseif ($request->input('source') !== RequestSourceEnum::SOURCE_ZAPIER && $user->twoFactor->enabled) {
                $checkIfAuthenticated = $this->twoFactorAuthService->checkIfAuthenticated($user->id, $deviceId);
                if (empty($checkIfAuthenticated)) {
                    if ($user->isTwoFactorNeeded($user->plan())) {
                        $this->twoFactorAuthService->generateTwoFactor($user->id, $user->email, $deviceId);
                        return $this->apiResponse(
                            400,
                            [],
                            ErrorTypes::INCOMPLETE_TWO_FACTOR,
                            'You need to complete your two factor authentication'
                        );
                    }
                }
            }

            $this->clearLoginAttempts($request);
            event(new UserLoggedIn($user));

            $user->defaultPlan = $user->defaultPlan;
            $user->plan = $user->plan();
            $user->globalPartialLeadSetting = $user->globalPartialLeadSetting;
            $user->show_onboarding = $user->showOnboarding();
            $user->load('onboarding');
            $user->load('roles');

            return $this->apiResponse(
                200,
                [
                    'token' => $token,
                    'user' => $user->toArray()
                ]
            );
        } else {
            return $this->apiResponse(
                401,
                [],
                ErrorTypes::INVALID_LOGIN_CREDENTIALS,
                'Please provide the correct credentials'
            );
        }
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return $this->apiResponse(
            200,
            $this->guard()->user()->toArray()
        );
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return $this->apiResponse(200);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    private function guard()
    {
        return Auth::guard();
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'email';
    }
}
