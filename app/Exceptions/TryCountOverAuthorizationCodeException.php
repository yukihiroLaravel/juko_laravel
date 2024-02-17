<?php

namespace App\Exceptions;

use App\Model\Student;
use Exception;

class TryCountOverAuthorizationCodeException extends Exception
{
    protected $message;

    public function __construct(
        $message,
        Student $student
    ) {
        // メッセージにユーザー情報のemailを追加
        $message = [
            $message,
            'email: ' . $student->email,
        ];

        $this->message = implode("\n", $message);
    }
}
