<?php

namespace App\Exceptions;

/**
 * Exception is the base class for all HRM Exceptions.
 */
class Exception extends \Exception
{
    public function __construct($message = null, $code = 500, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
