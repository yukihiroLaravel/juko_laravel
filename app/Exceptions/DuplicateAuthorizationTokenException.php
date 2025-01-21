<?php

namespace App\Exceptions;

use Exception;

class DuplicateAuthorizationTokenException extends Exception
{
    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
