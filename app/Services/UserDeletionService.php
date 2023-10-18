<?php

namespace App\Services;

use App\Dtos\ResultDto\ErrorResultDto;
use App\Dtos\ResultDto\ResultDto;
use App\Dtos\ResultDto\SuccessResultDto;
use App\Enums\ConfigKeyEnum;
use App\Mail\UserAccountDeleted;
use App\Modules\Subscription\Handlers\PaddleSubscriptionHandler;
use App\User;
use DB;
use App\Modules\Security\Services\AuthService;
use App\Enums\RequestSourceEnum;
use App\Models\InactiveUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Sentry;

class UserDeletionService
{
    /**
     * @var User
     */
    private User $userModel;

    /**
     * @var PaddleSubscriptionHandler
     */
    private PaddleSubscriptionHandler $paddleSubscriptionHandler;

    /**
     * AuthService instance.
     */
    protected AuthService $authService;

    /**
     * InactiveUser instance.
     */
    protected InactiveUser $inactiveUserModel;

    /**
     * UserDeletionService constructor.
     * @param User $user
     * @param PaddleSubscriptionHandler $paddleSubscriptionHandler
     * @param AuthService $authService
     * @param InactiveUser $inactiveUser
     */
    public function __construct(
        User $user,
        PaddleSubscriptionHandler $paddleSubscriptionHandler,
        AuthService $authService,
        InactiveUser $inactiveUser
    ) {
        $this->userModel = $user;
        $this->paddleSubscriptionHandler = $paddleSubscriptionHandler;
        $this->authService = $authService;
        $this->inactiveUserModel = $inactiveUser;
    }

    /**
     * @param User $user
     * @param string $source
     * @return ResultDto
     */
    public function deleteUser(User $user, string $source): ResultDto
    {
        try {
            $this->cancelSubscriptions($user);
        } catch (\Exception $e) {
            \Log::error($e);
            \Sentry\captureException($e);
            return new ErrorResultDto('Unable to cancel subscriptions');
        }

        $tempUser = $user->toArray();
        try {
            DB::beginTransaction();
            $user->forms->each(function ($form) {
                $form->current_experiment_id = null;
                $form->save();
            });
            $user->forceDelete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error($e);
            \Sentry\captureException($e);
            return new ErrorResultDto('Unable to delete user');
        }

        $mailDetails = [
            'name' => $tempUser['name'],
            'email' => $tempUser['email']
        ];

        Mail::to($tempUser['email'])->queue(new UserAccountDeleted($mailDetails, $source));

        return new SuccessResultDto();
    }

    /**
     * @param User $user
     */
    public function cancelSubscriptions(User $user): void
    {
        $plan = $user->plan();

        if (empty($plan->paddle_plan_id)) {
            return;
        }

        if (
            !$user->hasActiveSubscription($plan) ||
            $user->hasSubscriptionOnGracePeriod()
        ) {
            return;
        }

        $this->paddleSubscriptionHandler->cancel($plan->subscription->paddle_id);
    }

    /**
     * @param int $userId
     * @param string $deleteAt
     * @param string $source
     * @return ResultDto
     */
    public function requestUserDeletion(int $userId, string $deleteAt, string $source): ResultDto
    {
        try {
            $user = $this->userModel->findOrFail($userId);
            $savedInactiveUser = $this->inactiveUserModel->saveInactiveUser($userId, $deleteAt, $source);
            $slackDetails = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'deletion_date' => $savedInactiveUser->delete_at,
                'initiated_by' => $this->authService->getUser()->email,
            ];
            $this->sendSlackNotification($slackDetails);
        } catch (\Exception $e) {
            Sentry\captureException($e);
            return new ErrorResultDto('Unable to request user deletion');
        }

        return new SuccessResultDto();
    }

    /**
     * @param int $userId
     * @param string $source
     * @return ResultDto
     */
    public function cancelUserDeletion(int $userId, string $source): ResultDto
    {
        try {
            $this->inactiveUserModel->removeArchivedUser($userId, $source);
        } catch (\Exception $e) {
            \Sentry\captureException($e);
            return new ErrorResultDto('Unable to request user deletion');
        }

        return new SuccessResultDto();
    }

    /**
     * @param array $slackMessageDetails
     */
    public function sendSlackNotification(array $slackMessageDetails)
    {
        $slackUrl = config(ConfigKeyEnum::LEADGEN_SLACK_USER_DELETION_CHANNEL);
        Http::post($slackUrl, [
            'username' => 'Leadgen BOT',
            'attachments' => [
                [
                    'pretext' => 'Delete Initiated',
                    'title' => 'Account deletion requested',
                    'color' => '#ee6e73',
                    'title_link' => 'https://leadgenapp.io',
                    'fallback' => 'User Account Action',
                    'fields' => [
                        [
                            'title' => 'Details',
                            'value' => implode("\n", [
                                "User: {$slackMessageDetails['user_name']}",
                                "Email: {$slackMessageDetails['user_email']}",
                                "Deletion Date: {$slackMessageDetails['deletion_date']}",
                                "Initiated By: {$slackMessageDetails['initiated_by']}",
                            ]),
                            'short' => false,
                        ],
                    ],
                    'footer' => 'Powered by leadgen',
                    'ts' => time(),
                ]
            ]
        ]);
    }
}
