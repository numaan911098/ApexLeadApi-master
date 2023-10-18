<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DeleteInactiveAccountsJob;
use App\Jobs\MailInactiveAccountsJob;
use App\Enums\RequestSourceEnum;
use App\Models\InactiveUser;
use App\User;
use Carbon\Carbon;
use Sentry;

class DeleteInactiveAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leadgen:deleteInactiveAccounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete accounts which are inactive from two or more years';

    /**
     * InactiveUser
     *
     * @var InactiveUser
     */
    protected $inactiveUserModel;

    /**
     * Create a new command instance.
     * @param InactiveUser $inactiveUser
     * @return void
     */
    public function __construct(InactiveUser $inactiveUser)
    {
        parent::__construct();
        $this->inactiveUserModel = $inactiveUser;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $inactivePeriod = config('leadgen.gdpr.user_inactive_period');
        $allUsers = User::has('loginHistories')->get();
        foreach ($allUsers as $user) {
            if ($this->shouldProcessUser($user, $inactivePeriod)) {
                $mailDetails = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'final' => false
                ];
                $getInactiveUser = $this->inactiveUserModel->getInactiveUser(
                    $user->id,
                    RequestSourceEnum::SOURCE_COMMAND
                );
                if (empty($getInactiveUser)) {
                    // user just found to be inactive, send ist mail
                    $delete_at = Carbon::now()->addWeek();
                    $mailDetails['date'] = $delete_at;
                    $this->inactiveUserModel->saveInactiveUser(
                        $user->id,
                        $delete_at,
                        RequestSourceEnum::SOURCE_COMMAND
                    );
                    try {
                        dispatch(new MailInactiveAccountsJob($mailDetails));
                    } catch (\Exception $exception) {
                        Sentry\captureException($exception);
                    }
                } else {
                    // user is already inactive
                    $deleteDate = Carbon::parse($getInactiveUser->delete_at);
                    try {
                        if ($deleteDate->isToday() || $deleteDate->isPast()) {
                            dispatch(new DeleteInactiveAccountsJob(
                                $user,
                                RequestSourceEnum::SOURCE_COMMAND
                            ));
                        } elseif ($deleteDate->isTomorrow()) {
                            $mailDetails['date'] = $deleteDate;
                            $mailDetails['final'] = true;
                            dispatch(new MailInactiveAccountsJob($mailDetails));
                        }
                    } catch (\Exception $exception) {
                        Sentry\captureException($exception);
                    }
                }
            }
        }
    }

    /**
     * Check if the user should be processed.
     *
     * @param User $user
     * @param int  $inactivePeriod
     * @return bool
     */
    private function shouldProcessUser(User $user, int $inactivePeriod): bool
    {
        $userPlan = $user->plan();
        $hasNotLoggedIn = $user->hasNotLoggedIn(Carbon::now()->subYears($inactivePeriod));
        $isCustomer = $user->isCustomer();
        $hasActiveSubscription = $user->hasActiveSubscription($userPlan);
        $hasSubscriptionOnGracePeriod = $user->hasSubscriptionOnGracePeriod();

        return $hasNotLoggedIn && $isCustomer && !($hasActiveSubscription || $hasSubscriptionOnGracePeriod);
    }
}
