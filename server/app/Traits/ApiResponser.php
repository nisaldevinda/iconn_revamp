<?php

namespace App\Traits;

/**
 * ApiResponser is trait for represents HTTP response
 */
trait ApiResponser
{
    /**
     * Response represents an HTTP response in JSON format
     *
     * @param $data content that has to be passed to the frontend
     * @param $cookies add cookies to the response
     * @return \Illuminate\Http\JsonResponse
     *
     * usage:
     * $data => [
     *      'statusCode' => 200,
     *      'message' => "User has been created",
     *      'data' => ['id' => 1]
     * ]
     * $cookies => ['session', 'user-theme']
     *
     * Sample output:
     * new \Illuminate\Http\JsonResponse();
     */
    protected function jsonResponse($data, $cookies = [])
    {
        $status = $data['statusCode'];
        // unset($data['statusCode']);
        unset($data['error']);

        $response = response()->json($data, $status);
        // set cookies
        if ($cookies) {
            foreach ($cookies as $cookie) {
                $response->withCookie($cookie);
            }
        }
        return $response;
    }

    /**
     * Forbidden HTTP response in JSON format
     * 
     * Sample output:
     * new \Illuminate\Http\JsonResponse();
     */
    protected function forbiddenJsonResponse()
    {
        return response()->json(['message' => 'Permission denied', 'data' => null], 403);
    }
}
