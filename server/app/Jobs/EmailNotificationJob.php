<?php

namespace App\Jobs;

use Log;
use Exception;
use App\Library\Email;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;


/**
 * Name: EmailNotificationJob
 * Purpose: Handles Queue jobs for email notifications
 * Description: Handling queue jobs for emails via App\Library\Email class.
 * Module Creator: Chalaka 
 */
class EmailNotificationJob extends Job implements ShouldQueue
{
    protected $email;

    public function __construct(Email $email)
    {
        $this->email  = $email;
        $this->onQueue('email-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = $this->email->send();
        return $result;
    }
}
