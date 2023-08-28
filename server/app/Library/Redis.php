<?php

namespace App\Library;

use Predis;

/**
 * USAGE:
 * $redis = new Redis();
 * $redisConn = $redis->getConnection();
 * You can follow Prdis API with $redisConn
 */
class Redis
{
    private const USER_SESSION_PREFIX = 'user_session';
    private const USER_ROLE_PREFIX = 'user_role';

    static $connection;

    private $session;

    public function __construct(Session $session)
    {
        $configs = [
            'scheme' => config('redis.scheme'),
            'host' => config('redis.host'),
            'port' => config('redis.port')
        ];

		if (!empty(config('redis.database'))) $configs['database'] = config('redis.database');
		if (!empty(config('redis.password'))) $configs['password'] = config('redis.password');

        self::$connection = new Predis\Client($configs);

        $this->session = $session;
    }

    /**
     *
     * returns the created connection
     *
     */
    public function getConnection()
    {
        return self::$connection;
    }

    /**
     * set value
     *
     * Sample input
     * $key = 'test_key'
     * $value = 'Test Value'
     *
     */
    public function set($key, $value)
    {
        return self::$connection->set($key, $value);
    }

    /**
     * get value
     *
     * Sample input
     * $key = 'test_key'
     *
     * Sample output
     * $value = 'Test Value'
     *
     */
    public function get($key)
    {
        return self::$connection->get($key);
    }

    /**
     * delete value
     *
     * Sample input
     * $key = 'test_key'
     *
     */
    public function delete($key)
    {
        return self::$connection->del($key);
    }

    /**
     * get value
     *
     * Sample input
     * $key = 'test_key'
     *
     * Sample output
     * $value = 'Test Value'
     *
     */
    public function exists($key)
    {
        return self::$connection->exists($key);
    }

    /**
     * set user session
     *
     * Sample input
     * $id = '31e62bb2-1611-46ce-94a7-74776e04026e'
     * $value = {
     *   "user": {
     *       "id": 1,
     *       "email": "admin@emageia.com",
     *       ...
     *   },
     *   ....
     * }
     *
     */
    public function setUserSession($id, $value)
    {
        $key = self::USER_SESSION_PREFIX . ":" . $id;
        return self::$connection->set($key, json_encode($value));
    }

    /**
     * update user session (if exist)
     *
     * Sample input
     * $id = '31e62bb2-1611-46ce-94a7-74776e04026e'
     * $value = {
     *   "user": {
     *       "id": 1,
     *       "email": "admin@emageia.com",
     *       ...
     *   },
     *   ....
     * }
     *
     */
    public function updateUserSession($id, $value)
    {
        $key = self::USER_SESSION_PREFIX . ":" . $id;
        $isUserSessionExist = self::$connection->exists($key);

        if ($isUserSessionExist) {
            return self::$connection->set($key, json_encode($value));
        }

        return;
    }

    /**
     * delete user session
     *
     * Sample input
     * $id = '31e62bb2-1611-46ce-94a7-74776e04026e'
     *
     */
    public function deleteUserSession($id)
    {
        $key = self::USER_SESSION_PREFIX . ":" . $id;
        return self::$connection->del($key);

    }

    /**
     * get user session
     *
     * Sample input
     * $id = '31e62bb2-1611-46ce-94a7-74776e04026e'
     *
     * Sample output
     * $value = {
     *   "user": {
     *       "id": 1,
     *       "email": "admin@emageia.com",
     *       ...
     *   },
     *   ....
     * }
     *
     */
    public function getUserSession($id)
    {
        $key = self::USER_SESSION_PREFIX . ":" . $id;
        return json_decode(self::$connection->get($key));
    }

    /**
     * set user role
     *
     * Sample input
     * $id = 1
     * $value = {
     *       "id": 1,
     *       "title": "admin",
     *       ...
     * }
     *
     */
    public function setUserRole($id, $value)
    {
        $key = $this->getRolePerfix() . $id;
        return self::$connection->set($key, json_encode($value));
    }

    /**
     * set user role
     *
     * Sample input
     * $id = 1
     * $value = {
     *       "id": 1,
     *       "title": "admin",
     *       ...
     * }
     *
     */
    public function updateUserRole($id, $value)
    {
        $key = $this->getRolePerfix() . $id;

        $isUserRoleExist = self::$connection->exists($key);

        if ($isUserRoleExist) {
            return self::$connection->set($key, json_encode($value));
        }

        return;
    }

    /**
     * get user role
     *
     * Sample input
     * $id = 1
     *
     * Sample output
     * $value = {
     *       "id": 1,
     *       "title": "admin",
     *       ...
     * }
     *
     */
    public function getUserRole($id)
    {
        $key = $this->getRolePerfix() . $id;
        return json_decode(self::$connection->get($key));
    }

    /**
     * Get role prefix
     */
    private function getRolePerfix()
    {
        $tenantId = $this->session->getTenantId();
        return self::USER_ROLE_PREFIX . ":" . $tenantId . ":";
    }
}
