<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DeleteInactiveAccountsJob;
use App\Enums\RequestSourceEnum;
use App\User;
use Carbon\Carbon;
use Sentry;

class DeleteArchivedAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leadgen:deleteArchivedAccounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete accounts which are requested by Admin';

    /**
     * User
     *
     * @var User
     */
    protected $userModel;

    /**
     * Create a new command instance.
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        parent::__construct();
        $this->userModel = $user;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $archivedUsers = $this->userModel->whereHas('isInactiveUser', function ($query) {
            $query->where('delete_source', RequestSourceEnum::SOURCE_MANUAL_COMMAND);
        })->get();
        foreach ($archivedUsers as $user) {
            $deleteDate = Carbon::parse($user->delete_at);
            try {
                if ($deleteDate->isToday() || $deleteDate->isPast()) {
                    dispatch(new DeleteInactiveAccountsJob($user, RequestSourceEnum::SOURCE_MANUAL_COMMAND));
                }
            } catch (\Exception $exception) {
                Sentry\captureException($exception);
            }
        }
    }
}
