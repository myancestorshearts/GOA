<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Mysql;

use App\Common;

class Fulfillment extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Mysql\OrderGroup $order_group, Mysql\Label $label)
    {
        $from_address = Mysql\Address::find($label->from_address_id);

        $company_name = $from_address->company;

        $this->to($order_group->email, $order_group->name); 

        $this->from(env('MAIL_FROM_ADDRESS'), $company_name);

        $subject = $company_name . ' - Your Order Is On Its Way!';
        $this->subject($subject);

        $this->content = 'Your tracking number is: ' . $label->tracking;
        $this->tracking_link = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $label->tracking;
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
                'title' => 'Your Order Has Shipped',
                'content' =>  $this->content,
                'action_link' => $this->tracking_link,
                'action_text' => 'Track Package'
            ]);
    }
}
