<?php

namespace App\Http\Controllers;

use App\Enums\ConfigKeyEnum;
use App\Services\BlacklistIpService;
use App\Services\Lists\GeneralListService;
use App\Modules\Security\Services\AuthService;
use App\Services\UserDeletionService;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Events\UserRegistered;
use App\Mail\UserEmailVerification;
use App\Mail\RoleChanged;
use App\Mail\UserActivation;
use App\Mail\PasswordChanged;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\PlansEnum;
use App\Enums\RolesEnum;
use App\Enums\RequestSourceEnum;
use App\Http\Requests\UpdateUserBasicDetailsRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Newsletter;
use App\Plan;
use Facades\App\Services\Util;
use App\User;
use App\Role;
use Validator;
use Hash;
use Illuminate\Support\Facades\Mail;
use DB;
use Auth;
use Sentry;
use App\Models\UserSettings;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class UserController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * @var User
     */
    protected $userModel;
    /**
     * @var UserDeletionService
     */
    private UserDeletionService $userDeletionService;
    /**
     * @var AuthService
     */
    private AuthService $authService;
    /**
     * @var BlacklistIpService
     */
    private BlacklistIpService $blacklistIpService;
    /**
     * @var Newsletter
     */
    protected Newsletter $newsletterModel;
    /**
     * @var GeneralListService
     */
    private GeneralListService $generalListService;
    /**
     * @var Plan
     */
    protected Plan $planModel;

    /**
     * @var UserSettings
     */
    protected $userSettingsModel;

    /**
     * @var UserService
     */
    protected UserService $userService;

    /**
     * @var Carbon
     */
    protected Carbon $carbon;

    /**
     * UserController constructor.
     * @param User $user
     * @param BlacklistIpService $blacklistIpService
     * @param UserDeletionService $userDeletionService
     * @param AuthService $authService
     * @param Newsletter $newsletter
     * @param GeneralListService $generalListService
     * @param UserService $userService
     * @param Plan $plan
     * @param Carbon $carbon
     * @param UserSettings $userSettingsModel
     */
    public function __construct(
        User $user,
        BlacklistIpService $blacklistIpService,
        UserDeletionService $userDeletionService,
        AuthService $authService,
        Newsletter $newsletter,
        GeneralListService $generalListService,
        UserService $userService,
        Plan $plan,
        Carbon $carbon,
        UserSettings $userSettingsModel
    ) {
        $this->middleware('jwt.auth', ['only' => [
            'index',
            'show',
            'updateBasicDetails',
            'changeRole',
            'activation',
            'changePassword',
            'getProfileSettingData',
            'verifyPassword',
            'verifyEmailExists',
            'changeUserEmail',
            'changeUserPassword',
            'changeTwoFactorSettings'
        ]]);

        $this->userModel = $user;
        $this->blacklistIpService = $blacklistIpService;
        $this->userDeletionService = $userDeletionService;
        $this->authService = $authService;
        $this->newsletterModel = $newsletter;
        $this->generalListService = $generalListService;
        $this->userService = $userService;
        $this->planModel = $plan;
        $this->carbon = $carbon;
        $this->userSettingsModel = $userSettingsModel;
    }

    public function getUserLists(Request $request)
    {
        $user = $this->authService->getUser();
        if (!$user->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }

    public function index()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $query = User::withCount('forms', 'userLeads')
            ->with(['roles', 'oneToolUser'])
            ->whereHas('roles', function ($query) {
                $query->whereNotIn('name', [RolesEnum::ADMIN]);
            });

        if (!empty($_GET['q'])) {
            $q     = json_decode(urldecode($_GET['q']), true);
            $where = [];

            if (is_array($q) && count($q) > 1) {
                $where[] = 'users.' . $q[0];
                $where[] = 'like';
                $where[] = '%' . $q[1] . '%';
            }

            $query = $query->where([$where]);
        }

        if (empty($_GET['page'])) {
            $users = $query->latest()->get();

            foreach ($users as $user) {
                $user->plan = $user->plan();
            }

            return $this->apiResponse(200, $users->toArray());
        }

        $query = $query->orWhere('users.id', $user->id);

        $usersPagination = $query->latest()->paginate();

        $users = $usersPagination->items();

        foreach ($users as $user) {
            $user->plan = $user->plan();
        }

        $pagination = $usersPagination->toArray();
        unset($pagination['data']);

        return $this->apiResponse(200, $users, '', '', [], $pagination);
    }

    public function show(User $user)
    {
        $authUser = $this->authService->getUser();

        if ((!$authUser->isAdmin() || $user->isAdmin()) && $authUser->id !== $user->id) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $user->plan = $user->plan();

        $user->roles = $user->roles;

        $user->onboarding = $user->onboarding;

        $user->show_onboarding = $user->showOnboarding();

        $user->defaultPlan = $user->defaultPlan;

        $user->twoFactor = $user->twoFactor;

        $user->isInactiveUser = $user->isInactiveUser;

        return $this->apiResponse(200, $user->toArray());
    }

    /**
     * @param UpdateUserBasicDetailsRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBasicDetails(UpdateUserBasicDetailsRequest $request, User $user): JsonResponse
    {
        $result = $this->userService->updateBasicDetails($user, $request->all());
        if (!$result->success) {
            return Util::apiResponse(
                403,
                [],
                $result->errorCode,
                $result->error
            );
        }

        return $this->apiResponse(200, $result->value->toArray());
    }

    public function changeRole(UpdateUserRoleRequest $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isAdmin() || $user->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        try {
            DB::beginTransaction();

            $user->roles()->sync([]);

            $role = Role::where('name', $request->input('role'))->first();

            $user->roles()->attach($role);

            $user->load('roles');

            Mail::to($user)->send(new RoleChanged($user));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            Util::logException($e);
        }

        $user->load('roles');

        return $this->apiResponse(200, $user->toArray());
    }

    public function activation(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isAdmin() || $user->isAdmin()) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $validator = Validator::make($request->all(), [
            "active" => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $user->active = $request->input('active');
        $user->save();

        Mail::to($user)->send(new UserActivation($user));

        return $this->apiResponse(200, $user->toArray());
    }

    public function changePassword(Request $request, User $user)
    {
        $authUser = Auth::user();

        if ((!$authUser->isAdmin() || $user->isAdmin()) && $user->id !== $authUser->id) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $user->password = bcrypt($request->input('password'));
        $user->save();

        Mail::to($user)->send(new PasswordChanged($user, $request->input('password'), true));

        return $this->apiResponse(200, $user->toArray());
    }

    public function register(Request $request, UserSettings $userSettingsModel)
    {
        $requestIp = $request->ip();
        $tooManyAccountsPerDay = $this->userModel
            ->where('ip', $requestIp)
            ->whereDate('created_at', $this->carbon->now()->toDateString())
            ->count() >= config(ConfigKeyEnum::LEADGEN_USER_ACCOUNTS_PER_IP);
        if ($tooManyAccountsPerDay) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::TOO_MANY_ACCOUNTS,
                'Too many accounts are not allowed',
            );
        }

        if (
            $this->blacklistIpService->isIpBlocked($requestIp)
        ) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::BLACKLISTED_IP,
                'Your ip is blocked',
            );
        }

        if (
            config(ConfigKeyEnum::LEADGEN_REGISTRATION_RECAPTCHA_ENABLED) &&
            !in_array($request->input('source', ''), ['external_checkout'], true) &&
            !Util::verifyRecaptcha($request->input('grecaptcha_token', false))
        ) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_GRECAPTCHA_TOKEN,
                'Please complete recaptcha',
            );
        }

        $data = $request->only([
            'name',
            'email',
            'password',
            'agree_terms',
            'subscribe_newsletter',
            'signup_params',
            'planId',
            'isFreeTrial',
        ]);

        $rules = [
            'name' => 'required|regex:/^[a-zA-Z ]*$/|max:50',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'agree_terms' => 'required',
            'subscribe_newsletter' => 'required',
        ];

        $user = User::where('email', $data['email'])->first();

        if (!empty($user)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::EMAIL_ALREADY_EXIST,
                'The email has already been taken.'
            );
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please provide the correct data',
                $validator->errors()->toArray()
            );
        }

        $data['active']             = true;
        $data['password']           = bcrypt($data['password']);
        $data['verification_token'] = base64_encode(
            Hash::make($data['password'] . '' . mt_rand())
        );
        $data['ip'] = $requestIp;

        if ($request->has('planId')) {
            $data['sign_up_plan_id'] = $this->planModel->where('paddle_plan_id', $data['planId'])->first()->id;
        }

        if ($request->has('isFreeTrial') && $request->isFreeTrial) {
            $data['default_plan_id'] = $this->planModel->where('public_id', PlansEnum::FREE_TRIAL)->first()->id;
        }

        if ($request->has('signup_params')) {
            $data['signup_params'] = json_encode($data['signup_params']);
        }

        try {
            DB::beginTransaction();

            $user = $this->userModel->create($data);
            $customerRole = Role::where('name', RolesEnum::CUSTOMER)->first();
            $user->roles()->attach($customerRole);

            $this->newsletterModel->create([
                'user_id' => $user->id,
                'subscribed' => $user->subscribe_newsletter
            ]);

            DB::commit();

            event(new UserRegistered($user));

            $user->globalPartialLeadSetting = $user->globalPartialLeadSetting;

            return $this->apiResponse(201, array_merge($user->toArray(), [
                'token' => auth()->login($user),
            ], $data));
        } catch (\Exception $e) {
            DB::rollback();

            Util::reportSentry($e);

            Util::logException($e);

            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Unable to create user, something went wrong.'
            );
        }
    }

    public function verifyEmail($userId, $token)
    {
        $user = $this->userModel->findOrFail($userId);

        if ($user->verified) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::ACCOUNT_ALREADY_VERIFIED,
                'Your account is already verified'
            );
        } else {
            if ($token === $user->verification_token) {
                $user->verified = true;
                $user->verification_token = null;
                $user->active = true;
                $user->save();

                $userPlan = $user->plan();
                if ($userPlan->isFreeTrialPlan()) {
                    return $this->apiResponse(200, [
                        'token' => auth()->login($user),
                    ]);
                } else {
                    return $this->apiResponse(200);
                }
            } else {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::INVALID_VERIFICATION_TOKEN,
                    'Please submit valid token'
                );
            }
        }
    }

    public function sendEmailVerification(Request $request)
    {
        if (!$this->validateEmail($request)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please submit correct email address'
            );
        }

        $user = $this->userModel
            ->where('email', $request->input('email'))
            ->firstOrFail();

        if ($user->verified) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::ACCOUNT_ALREADY_VERIFIED,
                'Your account is already verified.'
            );
        }

        if ($user->isGoogleUser()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::ACCOUNT_GOOGLE_VERIFIED,
                'Your account is already verified by google.'
            );
        }

        $user->verified = false;
        $user->verification_token = base64_encode($user->password . '' . mt_rand());
        $user->save();

        Mail::to($user)
            ->send(new UserEmailVerification($user));

        return $this->apiResponse(200);
    }

    public function sendPasswordResetLink(Request $request)
    {
        if (!$this->validateEmail($request)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please submit correct email address'
            );
        }

        $user = $this->userModel
            ->where('email', $request->input('email'))
            ->firstOrFail();

        $response = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($response == Password::RESET_LINK_SENT) {
            return $this->apiResponse(200);
        }

        return $this->apiResponse(
            400,
            [],
            ErrorTypes::PASSWORD_RESET_ERROR,
            'Unable to send password reset link, please try again.'
        );
    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'token' => 'required',
            'userId' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please submit valid data',
                $validator->errors->toArray()
            );
        }

        $user = $this->userModel->findOrFail($request->input('userId'));

        $password = $request->input('password');

        $response = $this->broker()->reset(
            $this->credentials($request, $user),
            function ($user, $password) {
                $this->resetUserPassword($user, $password);
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            return $this->apiResponse(200);
        }

        return $this->apiResponse(
            400,
            [],
            ErrorTypes::PASSWORD_RESET_ERROR,
            'Unable to reset password, please try again.'
        );
    }

    /**
     * Get list of recently signups.
     *
     * @param int $count
     * @return void
     */
    public function signups($count)
    {
        $count = $count > 20 ? 20 : $count;

        $users = User::latest()->take($count)->get();

        $usersArray = [];
        foreach ($users as $user) {
            $name = explode(' ', $user->name);
            $name = array_shift($name);

            if (!empty($user->country)) {
                $name = $name . ' from ' . $user->country;
            }

            $usersArray[] = [
                'name' => $name,
                'created_at' => $user->created_at->diffForHumans(),
            ];
        }

        return $this->apiResponse(200, $usersArray);
    }

    /**
     * Request user deletion
     *
     * @param int $userId
     * @return void
     */
    public function requestUserDeletion(int $userId)
    {
        $authUser = $this->authService->getUser();

        // Check if the authenticated user is either an admin or the user being deleted
        if (!$authUser->isAdmin() && $authUser->id !== $userId) {
            return $this->apiResponse(
                401,
                [],
                ErrorTypes::UNAUTHORIZED,
                'Unauthorized to delete this account'
            );
        }

        // Perform the user deletion request
        $result = $this->userDeletionService->requestUserDeletion(
            $userId,
            $this->carbon->addHours(23),
            RequestSourceEnum::SOURCE_MANUAL_COMMAND
        );

        if (!$result->success) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_DELETE_ERROR,
                $result->error
            );
        }

        return $this->apiResponse(200);
    }

    /**
     * Cancel user deletion request
     *
     * @param int $userId
     * @return void
     */
    public function cancelUserDeletion(int $userId)
    {
        $authUser = $this->authService->getUser();

        // Check if the authenticated user is either an admin or the user being deleted
        if (!$authUser->isAdmin() && $authUser->id !== $userId) {
            return $this->apiResponse(
                401,
                [],
                ErrorTypes::UNAUTHORIZED,
                'Unauthorized to cancel deletion of this account'
            );
        }

        $result = $this->userDeletionService->cancelUserDeletion(
            $userId,
            RequestSourceEnum::SOURCE_MANUAL_COMMAND
        );
        if (!$result->success) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::RESOURCE_DELETE_ERROR,
                $result->error
            );
        }

        return $this->apiResponse(200);
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request, User $user)
    {
        return [
            'email' => $user->email,
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password'),
            'token' => $request->input('token')
        ];
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetUserPassword($user, $password)
    {
        $user->password = Hash::make($password);

        $user->save();

        event(new PasswordReset($user));
    }

    protected function validateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        return !$validator->fails();
    }

    /**
     * @param User $user
     * @return \Illuminate\Http\Response
     * action performed by user himself
     */
    public function getProfileSettings(User $user)
    {
        $authUser = $this->authService->getUser();

        // Verify auth user
        if ($user->id !== $authUser->id) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action.'
            );
        }

        $result = $this->userService->getProfileSettingData($authUser);

        // Check if the email change was successful or not
        $responseCode = $result ? 200 : 500;
        $responseMessage = $result ? 'Data found.'
        : 'An error occurred while fetching profile.';

        return $this->apiResponse($responseCode, $result, $responseMessage);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\Response
     * action performed by user himself
     */
    public function updateUserEmail(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|min:4|max:255',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please submit valid data',
                $validator->errors->toArray()
            );
        }

        $authUser = $this->authService->getUser();

        // Verify auth user
        if ($user->id !== $authUser->id) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action.'
            );
        }

        // Verify user's password
        if (!$this->userService->verifyPassword($authUser, $request['password'])) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'Password verification failed.'
            );
        }

        // Verify if new email already exists
        if ($this->userService->verifyEmailExists($authUser, $request['email'])) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::EMAIL_ALREADY_EXIST,
                'Email address is already in use.'
            );
        }

        // Check if email has changed
        if ($user->email === $request['email']) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'No changes to email address.'
            );
        }

        // Attempt to change the user's email
        try {
            $result = $this->userService->changeUserEmail($authUser, $request['email']);
        } catch (ThrottleRequestsException $throttleException) {
            Sentry\captureException($throttleException);
            return $this->apiResponse(
                429, // 429 is the HTTP status code for "Too Many Requests"
                [],
                ErrorTypes::TOO_MANY_ATTEMPTS,
                'Too many attempts. Please try again later.'
            );
        } catch (\Exception $genericException) {
            // Handle other exceptions here
            Sentry\captureException($genericException); // Optionally, capture the generic exception
            return $this->apiResponse(
                500, // You can choose an appropriate HTTP status code for generic errors
                [],
                ErrorTypes::GENERIC_ERROR,
                'An error occurred: ' . $genericException->getMessage()
            );
        }

        // Check if the email change was successful or not
        $responseCode = $result ? 200 : 500;
        $responseMessage = $result ? 'Email updated successfully.'
        : 'An error occurred while updating email.';

        return $this->apiResponse($responseCode, $result, $responseMessage);
    }

    /**
     * Change user password.
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     * action performed by user himself
     */
    public function updateUserPassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required|min:8|max:130',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'Please submit valid data',
                $validator->errors->toArray()
            );
        }

        $authUser = $this->authService->getUser();

        // Verify auth user
        if ($user->id !== $authUser->id) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action.'
            );
        }

        // Verify user's old password
        if (!$this->userService->verifyPassword($authUser, $request['oldPassword'])) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'Your password verification has failed.'
            );
        }

        // Check if password has changed
        if (Hash::check($request['newPassword'], $authUser->password)) {
            return $this->apiResponse(
                400,
                [],
                ErrorTypes::INVALID_DATA,
                'No changes to password.'
            );
        }


        // Attempt to change the user's password
        try {
            $result = $this->userService->changeUserPassword($authUser, $request['newPassword']);
        } catch (ThrottleRequestsException $throttleException) {
            Sentry\captureException($throttleException);
            // Handle the throttle exception here
            return $this->apiResponse(
                429, // 429 is the HTTP status code for "Too Many Requests"
                [],
                ErrorTypes::TOO_MANY_ATTEMPTS,
                'Too many attempts. Please try again later.'
            );
        }

        // Check if the password change was successful or not
        $responseCode = $result ? 200 : 500;
        $responseMessage = $result ? 'Password updated successfully.'
        : 'An error occurred while updating password.';

        return $this->apiResponse($responseCode, $result, $responseMessage);
    }

    /**
     * Change user two factor settings.
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     * action performed by user himself
     */
    public function updateTwoFactor(Request $request, User $user)
    {
        $authUser = $this->authService->getUser();

        // Verify auth user
        if ($user->id !== $authUser->id) {
            return $this->apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action.'
            );
        }

        $result = $this->userService->changeTwoFactorSettings($authUser, $request['twoFactor']);

        // Check if the password change was successful or not
        $responseCode = $result ? 200 : 500;
        $responseMessage = $result ? '2FA settings updated successfully.'
        : 'An error occurred while updating 2FA settings.';

        return $this->apiResponse($responseCode, $result, $responseMessage);
    }
}
