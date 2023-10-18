<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSettings;
use App\Modules\Security\Services\AuthService;
use App\User;

class UserSettingsController extends Controller
{
    protected $userSettingsModel;
    protected $user;

     /**
     * @var AuthService
     */
    private AuthService $authService;

    public function __construct(
        UserSettings $userSettingsModel,
        User $user,
        AuthService $authService
    ) {
        $this->userSettingsModel = $userSettingsModel;
        $this->user = $user;
        $this->authService = $authService;
    }

    public function getUserSettings()
    {
        $emailVerification = $this->userSettingsModel->get();
        return $this->apiResponse(200, $emailVerification->toArray());
    }

    public function update(Request $request)
    {
        $authUser = $this->authService->getUser();
        $emailVerification = $this->userSettingsModel->where('id', $request->id)->update([
            'email_verification_enabled' => $request->email_verification_enabled
        ]);
        if ($authUser->isAdmin()) {
            return $this->apiResponse(200);
        }
    }
}
