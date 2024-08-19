<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Mysql;

use App\Common;

class EmailVerification extends Mailable
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
        $encrypted_string = encrypt($user->email);

        $host = Common\Functions::getHost();

        $this->verify_email_link = $host . '/verify?key=' . $encrypted_string;

        $this->to($user->email, $user->name);
        
        $subject = env('APP_ENV') == 'prod' ? 'Verification Email' : 'Dev - Verification Email';
        $this->subject($subject);
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
                'title' => 'Welcome',
                'content' => 'Thank you for signing up for ' . env('APP_NAME') . '.' . ' Please verify your email.',
                'action_link' => $this->verify_email_link,
                'action_text' => 'Verify Email'
            ]);
    }
}
