<?php

namespace App\Traits;

/**
 * ApiResponser is trait for represents HTTP response
 */
trait JobResponser
{
    /**
     * Job response
     *
     * @param $error error status
     * @param $message error message
     * @return array
     *
     * usage:
     * $error => false
     * $message => null
     *
     * Sample output:
     * ['error' => false, $message => null]
     */
    protected function jobResponse($error = false, $message = null)
    {
        return ['error' => $error, 'message' => $message];
    }
}
