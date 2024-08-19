<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mysql;

class ClientApproved extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Mysql\User $user)
    {
        //
        
        $this->to($user->email, $user->name);
        $this->subject('Account Approved!');

        $this->content = 'Hi ' . $user->name . '</br>';

        $this->content .= '<p>Your account has been approved. Login to start enjoying shipping labels at a competitive price.</p>';
        
        $this->login_link = $_SERVER['HTTP_ORIGIN'];
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
                'title' => 'You have been Approved',
                'content' => $this->content,
                'action_link' => $this->login_link,
                'action_text' => 'Login'
            ]);
    }
}
