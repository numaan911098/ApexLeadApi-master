<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserAccountDeleted extends Mailable
{
    use Queueable;
    use SerializesModels;


    /**
     * Email details.
     *
     * @var array
     */
    public $details;

    /**
     * Email source.
     *
     * @var string
     */
    public $source;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $details, string $source)
    {
        $this->details = $details;
        $this->source = $source;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.users.deleted')
            ->from(config('mail.from.address'))
            ->subject('User Account Deleted Permanently')
            ->with([
                'details' => $this->details,
                'source' => $this->source
            ]);
    }
}
