<?php

namespace App\Exceptions;

use Exception;

class ValidationErrorException extends Exception
{
    protected $statusCode;

    public function __construct($message = '', $statusCode = 403)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
