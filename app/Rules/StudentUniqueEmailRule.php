<?php

namespace App\Rules;

use App\Model\Student;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StudentUniqueEmailRule implements ValidationRule
{
    protected $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * バリデーションの実行。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 自分自身のメールアドレスの場合は無視
        if ($this->email && $value === $this->email) {
            return;
        }

        // メールアドレスが一意かどうかを確認
        if (Student::where('email', $value)->exists()) {
            // エラーを返す
            $fail('The :attribute has already been taken.');
        }
    }
}
