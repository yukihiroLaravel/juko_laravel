<?php

namespace App\Exceptions;

use Exception;

class AuthorizationCodeException extends Exception
{
    protected $message;

    public function __construct($student)
    {
        
        $message = [
        'Error Message: 以下のユーザーの認証コードが重複しました',
        //"$student->id",
        ];

        $this->message = implode("\n", $message);
    }
}