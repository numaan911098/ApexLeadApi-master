<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\UserDeletionService;
use App\User;
use Exception;
use Sentry;

class DeleteInactiveAccountsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $command;

    /**
     * UserDeletionService instance.
     *
     * @var UserDeletionService
     */
    protected $userDeletionService;

    /**
     * Create a new job instance.
     * @param User $user
     * @param string $command
     * @return void
     */
    public function __construct(User $user, string $command)
    {
        $this->user =  $user;
        $this->command = $command;
    }

    /**
     * Execute the job.
     * @param UserDeletionService $userDeletionService
     * @return void
     */
    public function handle(UserDeletionService $userDeletionService)
    {
        $this->userDeletionService = $userDeletionService;
        $this->userDeletionService->deleteUser($this->user, $this->command);
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed($exception)
    {
        Sentry\captureException($exception);
    }
}
