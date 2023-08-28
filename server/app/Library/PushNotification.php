<?php
namespace App\Library;

use Log;
use Exception;
use App\Library\Store;
use App\Library\JsonModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Library\Interfaces\ModelReaderInterface;
use App\Traits\JsonModelReader;


/**
 * Purpose: Provides library features related to PushNotification handling in the app.
 * Description: Maintains notification data in notification table.
 * Module Creator: Chalaka 
 */
class PushNotification
{
    
    use Queueable, SerializesModels, JsonModelReader;

    private $store;
    private $notificationModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->notificationModel = $this->getModel('notification', true);
    }




    /**
     * Create new notifications.
     * 
     * @param $notification array contains notification details
     * @return object
     * 
     * usage:
     * $notification => [
     *      'userId'=>1, 'message'=>'Hello This is notification number 1', 'messageCategory'=>'email', 'isRead'=>false
     * ]
     * 
     * 
     * Sample output:
     * { 'id' => 1, userId'=>1, 'message'=>'Hello This is notification number 1', 'messageCategory'=>'email', 'isRead'=>false }
     */
    public function createNotification($notification)
    {
        try {
            if (empty($notification['userId'])) {
                throw new Exception('User ID is not defined.');
            }
            return $this->store->insert($this->notificationModel, $notification, true);
        } catch (\Throwable $e) {
            throw $e;
        }
    }


    /**
     * Get unread notifications (notifications where 'isRead' => false)
     * 
     * @param $id user id of the requested user
     * @return object
     * 
     * usage:
     * $id => 1
     * 
     * 
     * Sample output:
     * { ['id' => 1, userId'=>1, 'message'=>'Hello, notification number 1 for user 1', 'messageCategory'=>'email', 'isRead'=>false], 
     *   ['id' => 2, userId'=>1, 'message'=>'Hello, notification number 2 for usere 1', 'messageCategory'=>'email', 'isRead'=>false]
     * }
     */
    public function getUnreadNotificationsByUserId($id)
    {
        try {
            if (empty($id)) {
                throw new Exception('User ID is not defined.');
            }
            
            return $this->store->getFacade()::table('notification')->where('userId', $id)->where('isRead', false)->get();
        } catch (\Throwable $e) {
            throw $e;
        }
    }


    /**
     * Used to send notifications to the front-end (Customize based on the requirement).
     * 
     * @param $userId user id of the requested user
     * @return object
     * 
     * usage:
     * $userId => 1
     * 
     * 
     * Sample output:
     * { ['id' => 1, userId'=>1, 'message'=>'Hello, notification number 1 for user 1', 'messageCategory'=>'email', 'isRead'=>false], 
     *   ['id' => 2, userId'=>1, 'message'=>'Hello, notification number 2 for usere 1', 'messageCategory'=>'email', 'isRead'=>false]
     * }
     */
    public function send($userId)
    {
        try {
            return $this->getUnreadNotificationsByUserId($userId);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

}

