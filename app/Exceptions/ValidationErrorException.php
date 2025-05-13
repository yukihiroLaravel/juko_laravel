<?php

namespace App\Exceptions;

use Exception;

class ValidationErrorException extends Exception
{
    public function __construct($message = '', protected $statusCode = 403)
    {
        parent::__construct($message, $this->statusCode);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
