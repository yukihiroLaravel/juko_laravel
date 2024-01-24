<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;
use App\Model\Student;

class StudentUniqueEmailRule implements Rule
{
    protected $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function passes($attribute, $value)
    {
        // 自分自身のメールアドレスの場合は無視
        if ($this->email && $value === $this->email) {
            return true;
        }

        // メールアドレスが一意かどうかを確認
        return Student::where('email', $value)->count() === 0;
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}
