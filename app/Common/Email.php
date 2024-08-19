<?php
namespace App\Common;

use Mail;

class Email {

	/**purpose
	 *   send an email
	 * args
	 *   $email, $mailer
	 * returns
	 *   uuid
	 */
	public static function sendMailer($mailer) {

       /* Mail::send($view, $pagedata, function ($message) use ($subject, $from, $from_name, $reply, $reply_name, $to, $to_name, $email_correspondence) {
            $message->from($from, $from_name);
            $message->replyto($reply, $reply_name);
            $message->to($to, $to_name)->subject($subject);
            $message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', 'EliteWorks-Monitoring');
            $message->getHeaders()->addTextHeader('X-EW-EMAIL-CORRESPONDENCE-ID', $email_correspondence->email_correspondence_id);
            $message->getHeaders()->addTextHeader('X-EW-APPLICATION-KEY', EliteConfig::get('APPLICATION_KEY'));
        });*/

        Mail::send($mailer);
    }
}

?>