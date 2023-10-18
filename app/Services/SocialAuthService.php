<?php

namespace App\Services;

use App\User;
use App\Plan;
use App\Role;
use App\Models\Newsletter;
use App\Models\GoogleUser;
use App\Enums\RolesEnum;
use App\Enums\SocialProvidersEnum;
use App\Enums\PlansEnum;
use App\Events\SocialUserRegistered;
use Illuminate\Support\Facades\DB;
use Sentry;

class SocialAuthService
{
    /**
     * @var User
     */
    private $userModel;

    /**
     * @var Plan
     */
    private $planModel;

    /**
     * @var Role
     */
    private $roleModel;

    /**
     * @var Newsletter
     */
    private Newsletter $newsletterModel;

    /**
     * @var GoogleUser
     */
    private GoogleUser $googleUserModel;

    /**
     * SocialAuthService constructor.
     * @param User $user
     * @param Plan $plan
     * @param Role $role
     * @param Newsletter $newsletter
     * @param GoogleUser $googleUser
     */
    public function __construct(
        User $user,
        Plan $plan,
        Role $role,
        Newsletter $newsletter,
        GoogleUser $googleUser
    ) {
        $this->userModel = $user;
        $this->planModel = $plan;
        $this->roleModel = $role;
        $this->newsletterModel = $newsletter;
        $this->googleUserModel = $googleUser;
    }

    /**
     * @param string $user
     * @return User|null
     */
    public function checkIfExists(string $email): ?User
    {
        $checkUser = $this->userModel->where('email', $email)->first();

        if (empty($checkUser)) {
            return null;
        }

        return $checkUser;
    }

    /**
     * @param array $user
     * @return User|null
     */
    public function addToProvider(array $data): ?User
    {
        try {
            DB::beginTransaction();

            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'agree_terms' => true,
                'subscribe_newsletter' => true,
                'active' => true,
                'verified' => true
            ];

            if (isset($data['planId']) && $data['planId']) {
                $userData['sign_up_plan_id'] = $this->planModel->where('paddle_plan_id', $data['planId'])->first()->id;
            }

            if (isset($data['isFreeTrial']) && $data['isFreeTrial']) {
                $userData['default_plan_id'] = $this->planModel->where('public_id', PlansEnum::FREE_TRIAL)->first()->id;
            }

            $user = $this->userModel->create($userData);

            $customerRole = $this->roleModel->where('name', RolesEnum::CUSTOMER)->first();
            $user->roles()->attach($customerRole);

            $this->newsletterModel->create([
                'user_id' => $user->id,
                'subscribed' => $user->subscribe_newsletter
            ]);

            if ($data['provider'] === SocialProvidersEnum::GOOGLE) {
                $this->googleUserModel->create([
                    'provider' => $data['provider'],
                    'google_id' => $data['google_user_id'],
                    'access_token' => $data['access_token'],
                    'user_id' => $user->id
                ]);
            }

            DB::commit();
            event(new SocialUserRegistered($user));
            return $user;
        } catch (\Exception $e) {
            Sentry\captureException($e);
            DB::rollBack();
            return null;
        }
    }
}
