<?php
namespace App\Services;

use Log;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\JsonModel;
use App\Library\Interfaces\ModelReaderInterface;
use App\Traits\JsonModelReader;

/**
 * Purpose: Performs library level tasks related to the User Login.
 * Description: User Service class is called to create and retrive user logins.
 * to User Login (basic operations and others).
 * Module Creator: Chalaka 
 */
class LoginHistoryService
{
    use JsonModelReader;

    private $store;
    private $loginHistoryModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->loginHistoryModel = $this->getModel('loginHistory', true);
    }

    /**
     * Create a login record.
     * 
     * @param  $data model data
     * @return object | array | Exception
     * 
     * Usage:
     * 
     *userLogin => [ 
     *      'id' => '1',
     *      'userId' => '1',
     *      'status' => 0,
     *      'loggedIp' => 127.1.1.1,
     *      'createdAt' => loginTimestamp
     * ]
     *
     * Sample output:
     * {
     *      'id' => '1',
     *      'userId' => '1',
     *      'status' => 0,
     *      'loggedIp' => 127.1.1.1,
     *      'createdAt' => loginTimestamp
     * }
     * 
     */
    public function createLoginHistory($userLogin)
    {
        try {
            return $this->store->insert($this->loginHistoryModel, $userLogin, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return 'Failed to save log activity.';
        }
    }


    /**
     * Retrive a login record by user id.
     * 
     * @param  $userId user id
     * @return object | array | Exception
     * 
     * Usage:
     * 
     *userId => 1
     *
     * Sample output:
     * {
     *      'id' => '1',
     *      'userId' => '1',
     *      'status' => 0,
     *      'loggedIp' => 127.1.1.1,
     *      'createdAt' => loginTimestamp
     * }
     * 
     */
    public function getUserLoginHistoryByUserId($userId)
    {
        try {
            if (empty($userId)) {
                throw new Exception('User ID is not defined.');
            }
            return $this->store->getFacade()::table('loginHistory')->where('userId', $userId)->get();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return 'Failed to retrive log activity.';
        }
    }

}