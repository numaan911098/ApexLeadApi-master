<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;

class PasswordChanged extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $user;

    public $password;

    /**
     * Mail details
     * @var bool
     */
    public $isAdmin;

    /**
     * Create a new message instance.
     *
     * @param User $user - The user for whom the password is changed.
     * @param string $password - The new password.
     * @param bool $flag - A flag to determine if the user is an admin.
     *
     * @return void
     */
    public function __construct(User $user, $password, bool $flag)
    {
        $this->user = $user;
        $this->password = $password;
        $this->isAdmin = $flag;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.users.password-changed');
    }
}
