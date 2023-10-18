<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Mail;
use App\Mail\PastDueUsersMail;
use App\Models\PastDueUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Sentry;

class MailPastDueUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leadgen:mailPastDueUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get users to update their bank details. Send email after failed billing attempt';

    /**
     * @var PastDueUser
     */
    private $pastDueUserModel;

    /**
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * Create a new command instance.
     * @param SubscriptionService $subscriptionService
     * @return void
     */
    public function __construct(
        SubscriptionService $subscriptionService,
        PastDueUser $pastdue
    ) {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
        $this->pastDueUserModel = $pastdue;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = App::make(User::class);
        $allUsers = $users->all();
        foreach ($allUsers as $user) {
            if ($user->isPastDue()) {
                $userPlan = $user->plan();
                $apiResponse = $this->subscriptionService->listPastDueUsers($user->id, $userPlan->subscription_id);
                $mailDetails = [
                    'name' => $user->getFirstNameAttribute(),
                    'url' => $apiResponse[0]['update_url']
                ];

                $getSavedPastDueUser = $this->pastDueUserModel->getSavedPastDueUser($user->id);
                if (empty($getSavedPastDueUser)) {
                    $mailDetails['first'] = true;
                    $this->pastDueUserModel->savePastDueUser($user->id, $userPlan->subscription_type);
                    try {
                        Mail::to($apiResponse[0]['user_email'])->send(new PastDueUsersMail($mailDetails));
                    } catch (\Exception $exception) {
                        Sentry\captureException($exception);
                    }
                } else {
                    $sendDate = Carbon::parse($getSavedPastDueUser->send_at);
                    $mailDetails['first'] = false;
                    if ($sendDate->isToday()) {
                        try {
                            Mail::to($apiResponse[0]['user_email'])->send(new PastDueUsersMail($mailDetails));
                        } catch (\Exception $exception) {
                            Sentry\captureException($exception);
                        }
                    }
                }
            } else {
                $this->pastDueUserModel->deletePastDueUser($user->id);
            }
        }
    }
}
