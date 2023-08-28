<?php

namespace App\Jobs;

use App\Library\PushNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


/**
 * Name: PushNotificationJob
 * Purpose: Handles Queue jobs for push notifications
 * Description: Handling queue jobs for emails via App\Library\PushNotification class.
 * Module Creator: Chalaka 
 */
class PushNotificationJob extends Job implements ShouldQueue
{
    protected $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pushNotification = new pushNotification();
        $pushNotification->send($this->notification['userId']);
    }
}
