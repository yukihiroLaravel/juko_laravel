<?php

namespace App\Exceptions;

use Exception;

class ExpiredAuthorizationCodeException extends Exception
{
    protected $message;

    public function __construct(
        string $message,
        string $email
    ) {
        // メッセージにユーザー情報のemailを追加
        $message = [
            $message,
            'email: '.$email,
        ];

        $this->message = implode("\n", $message);
    }
}
