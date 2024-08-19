<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mysql;

class ReferralInvite extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Mysql\User $user, $name, $email)
    {
        $this->to($email, $name);
        $this->subject('Invitation to ' . env('APP_NAME') . '!');

        $this->content = 'Hi ' . $name . '</br>';

        $this->content .= "<p>$user->name has invited you to " . env('APP_NAME') . '.  Click the button below to sign up and start shipping!</p>';
        
        $this->signup_link = $_SERVER['HTTP_ORIGIN'] . "/register?code=$user->referral_code";

    }

    /**
     * Build the message
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.generic')
            ->with([
                'title' => 'You have been invited to ' . env('APP_NAME'),
                'content' => $this->content,
                'action_link' => $this->signup_link,
                'action_text' => 'Sign Up Today'
            ]);
    }
}
