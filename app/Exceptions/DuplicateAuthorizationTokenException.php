<?php

namespace App\Exceptions;

use App\Model\Student;
use Exception;

class DuplicateAuthorizationTokenException extends Exception
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
