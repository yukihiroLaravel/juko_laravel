<?php

namespace App\Exceptions;

use Exception;

class TryCountOverAuthorizationCodeException extends Exception
{
    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
