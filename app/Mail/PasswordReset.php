<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Mysql;

use App\Common;
class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Mysql\User $user)
    {
        // create encrypted email
        $encrypted_string = encrypt($user->email . '#' . strtotime('+30 minutes', time()));

        $host = Common\Functions::getHost();

        $this->password_reset_link = $host . '/set?key=' . $encrypted_string;

        $this->to($user->email, $user->name);
        
        $subject = env('APP_ENV') == 'prod' ? 'Password Set' : 'Dev - Password Set';
        $this->subject($subject);

        $this->content = '<p>Dear ' . $user->name . ',<br/>A request has been made to set or reset your password. If you did not make this request please disregard this email.</p>';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.generic')
            ->with([
                'title' => 'Password Reset Request',
                'content' => $this->content,
                'action_link' => $this->password_reset_link,
                'action_text' => 'Set Password'
            ]);
    }
}
