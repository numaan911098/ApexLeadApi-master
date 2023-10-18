<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\DeleteUserWarningMail;
use Exception;
use Sentry;

class MailInactiveAccountsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new DeleteUserWarningMail($this->details);
        Mail::to($this->details['email'])->send($email);
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
