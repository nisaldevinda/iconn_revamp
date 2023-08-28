<?php

namespace App\Library;

use Log;
use Exception;
use Illuminate\Support\Facades\Mail;
/**
 * Purpose: Provides library features related to email handling in the app.
 * Description: 
 *      Mail configurations are available in the config/mail.php file.
 *      Email templates are located in resources/views/email folder. 
 * Module Creator: Chalaka 
 * 
 * ToDo: attachments
 */
class Email
{
    private $fromMail;
    protected $view; 
    protected $mailTo;
    protected $mailSubject; 
    protected $mailCC;
    protected $data;


    public function __construct(string $views = '', $mailTo = [], string $mailSubject = '', $mailCC = [], $data = [])
    {
        $this->view = $views; 
        $this->mailTo = $mailTo;
        $this->mailSubject = $mailSubject; 
        $this->mailCC = $mailCC;
        $this->data = $data;
        $this->fromMail =config('mail.from.address', 'no-reply@iconnhrm.io');
    }

    /**
     * Send emails to given addresses with or without attachments.
     * 
     * @param $view email template name
     * @param $maiilTo array of Receiving emails
     * @param $mailSubject Subject of the email
     * @param $mailCC array of cc emails
     * @param $maiBody Body of the email that contains the specific message
     * @param $attachment Attachments
     * @return string
     * 
     * usage:
     * $view => "emails.sampleMail",
     * $mailTo => ["john@gmail.com","john2@gmail.com","john3@gmail.com"],
     * $mailSubject => 1,
     * $mailCC => ["andrea@gmail.com", "andrea2@gmail.com", "andrea3@gmail.com"],
     * $data => "I'm writing this message regarding......."
     * 
     * 
     * Sample output:
     * 'Email Sent Successfully'
     */
    public function send()
    {

        try {

            if (empty($this->mailTo)) {
                throw new Exception('Mail Receiver is not defined.');
            }
            if (empty($this->mailSubject)) {
                throw new Exception('Mail subject is not defined.');
            }
            
    
            Mail::send($this->view, ['data' => $this->data], function ($message){
                $message->from($this->fromMail);

                foreach ($this->mailTo as $mail) {
                    $message->to($mail);
                }
                $message->subject($this->mailSubject);

                if (!empty($this->mailCC)) {
                    foreach ($this->mailCC as $mail) {
                        $message->cc($mail);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}