<?php

namespace App\Exceptions;

use Exception;

class DuplicateAuthorizationCodeException extends Exception
{
    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
