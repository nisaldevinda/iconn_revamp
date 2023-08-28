<?php

namespace App\Traits;

/**
 * ServiceResponser is trait for represents standard error handling format
 */
trait ServiceResponser
{
    /**
     * Following function is called to generate the error response.
     *
     * @param $statusCode error response code
     * @param $message error message
     * @param $data content that has to be passed to the frontend
     * @return int | String | array
     *
     * usage:
     * $username => "john",
     * $email => "john@gmail.com",
     * $userId => 1
     *
     *
     * Sample output:
     * $error => true,
     * $statusCode => 400,
     * $message => "Invalid User",
     * $data => {"username": "John"}
     */
    public function error($statusCode, $message, $data = [])
    {
        return ['error' => true, 'statusCode' => $statusCode, 'message' => $message, 'data' => $data];
    }


    /**
     * Following function is called to generate the success response.
     *
     * @param $statusCode error response code
     * @param $message error message
     * @param $data content that has to be passed to the frontend
     * @return int | String | array
     *
     * usage:
     * $username => "john",
     * $email => "john@gmail.com",
     * $userId => 1
     *
     *
     * Sample output:
     * $error => false,
     * $statusCode => 200,
     * $message => "User Created Successfully",
     * $data => {"username": "John"}
     */
    public function success($statusCode, $message, $data = [])
    {
        return ['error' => false, 'statusCode' => $statusCode, 'message' => $message, 'data' => $data];
    }
}
