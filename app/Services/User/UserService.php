<?php

namespace App\Services\User;

use App\Dtos\ResultDto\ErrorResultDto;
use App\Dtos\ResultDto\ResultDto;
use App\Dtos\ResultDto\SuccessResultDto;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Mail\PasswordChanged;
use App\Mail\UserEmailVerification;
use App\Models\OnboardingData;
use App\Models\TwoFactorSetting;
use App\Modules\Security\Services\AuthService;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mail;
use Sentry;

class UserService
{
    private User $userModel;

    private AuthService $authService;

    private TwoFactorSetting $twoFactorSettingModel;

    /**
     * @var OnboardingData
     */
    private OnboardingData $onboardingDataModel;

    public function __construct(
        User $userModel,
        AuthService $authService,
        TwoFactorSetting $twoFactorSetting,
        OnboardingData $onboardingData
    ) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->twoFactorSettingModel = $twoFactorSetting;
        $this->onboardingDataModel = $onboardingData;
    }

    public function updateBasicDetails(User $user, array $details = []): ResultDto
    {
        $authUser = $this->authService->getUser();

        if ((!$authUser->isAdmin() || $user->isAdmin()) && $user->id !== $authUser->id) {
            return new ErrorResultDto(
                'You are not Authorized for this action',
                ErrorTypes::UNAUTHORIZED
            );
        }
        try {
            DB::beginTransaction();

            $twoFactor = $this->twoFactorSettingModel->where('user_id', $user->id)->first();
            $twoFactor->enabled = $details['twoFactor'];
            $twoFactor->save();

            $existingUserWithEmail = $this->userModel
                ->where('email', $details['email'])
                ->first();
            if (!$existingUserWithEmail) {
                $user->name = $details['name'];
                $user->email = $details['email'];
                $user->verification_token = base64_encode($user->password . '' . mt_rand());
                $user->verified = 0;
                $user->save();

                Mail::to($user)
                    ->send(new UserEmailVerification($user));

                DB::commit();
                return new SuccessResultDto($user);
            }

            if ($existingUserWithEmail->id !== $user->id) {
                DB::rollBack();
                return new ErrorResultDto(
                    'You are not Authorized to use this email',
                    ErrorTypes::UNAUTHORIZED
                );
            }

            $user->name = $details['name'];
            $user->save();

            DB::commit();
            return new SuccessResultDto($user);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            DB::rollBack();
            return new ErrorResultDto(
                'Something went wrong.',
                ErrorTypes::INVALID_DATA
            );
        }
    }

    /**
     * Get user profile settings data
     *
     * @param User $user The user to update email for.
     * @return array|null Result of the operation.
     * action performed by user himself
     */
    public function getProfileSettingData(User $user): ?array
    {
        try {
            // Ensure that the user object represents the authenticated user
            $authUser = $this->authService->getUser();
            if ($user->id !== $authUser->id) {
                // Handle unauthorized access here
                return null;
            }

            // Load the two-factor setting (if applicable)
            $twoFactorSetting = $this->twoFactorSettingModel->where('user_id', $user->id)->first();

            // Check if the user has two-factor authentication enabled
            $isTwoFactorEnabled = $twoFactorSetting ? $twoFactorSetting->enabled : false;

            // Check if the user is marked as inactive
            $isUserInactive = $user->isInactiveUser ? true : false;

            // Return the user profile settings data with only non-sensitive information
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_google_user' => $user->isGoogleUser(),
                'has_credential' => !empty($user->password),
                'two_factor_enabled' => $isTwoFactorEnabled,
                'is_inactive' => $isUserInactive,
            ];
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Verify user's password.
     *
     * @param User $user The user for whom to verify the password.
     * @param string $password The password to verify.
     * @return bool Returns true if the password is correct, otherwise false.
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Verify user's email.
     *
     * @param User $user The user for whom to verify the email.
     * @param string $email The email to check.
     * @return bool Returns true if the email is found, otherwise false.
     */
    public function verifyEmailExists(User $user, string $email): bool
    {
        // Check if the new email already exists for another user
        $existingUserWithEmail = $this->userModel
            ->where('email', $email)
            ->where('id', '!=', $user->id)
            ->first();
        if (!$existingUserWithEmail) {
            return false;
        }

        return true;
    }

    /**
     * Update user email.
     *
     * @param User $user The user to update email for.
     * @param string $email - new email.
     * @return array|null Result of the operation.
     * action performed by user himself
     */
    public function changeUserEmail(User $user, string $email): ?array
    {
        try {
            // Update the user's email
            $user->email = $email;
            $user->verification_token = base64_encode($user->password . '' . mt_rand());
            $user->verified = 0;
            $user->save();
            Mail::to($user)->send(new UserEmailVerification($user));

            return $user->toArray();
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Update user password.
     *
     * @param User $user The user to update password for.
     * @param string $password - new password.
     * @return array|null Result of the operation.
     * action performed by user himself
     */
    public function changeUserPassword(User $user, string $password): ?array
    {
        try {
            // Update the user's password
            $user->password = bcrypt($password);
            $user->save();
            Mail::to($user)->send(new PasswordChanged($user, $password, false));

            return $user->toArray();
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Update user 2FA settings.
     *
     * @param User $user The user to update password for.
     * @param boolean $twoFactor - new 2fa setting.
     * @return array|null Result of the operation.
     * action performed by user himself
     */
    public function changeTwoFactorSettings(User $user, bool $twoFactor): ?array
    {
        try {
            $twoFactorSetting = $this->twoFactorSettingModel->where('user_id', $user->id)->first();
            $twoFactorSetting->enabled = $twoFactor;
            $twoFactorSetting->save();

            return $twoFactorSetting->toArray();
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Save user onboarding details
     * @param array $data
     * @param int $userId
     * @return null|array
     */
    public function saveOnboardingData(array $data): ?array
    {
        try {
            $onboardingData = $this->onboardingDataModel->updateOrCreate(
                ['user_id' => $this->authService->getUserId()],
                [
                    'industry' => $data['industry'],
                    'role' => $data['role'],
                    'goal' => $data['goal'],
                    'first_heard' => $data['firstHeard'],
                ]
            );
            return $onboardingData->toArray();
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return null;
        }
    }
}
