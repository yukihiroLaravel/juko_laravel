<?php

namespace App\Rules;

use App\Enums\Lesson\StatusEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LessonStatusRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {       
        // 送られてきた値（$value）をEnumの型に変更する
        $status = StatusEnum::tryFrom($value);
        // public または private 以外の場合はエラーにする（draftもここで弾かれる）
        if ($status !== StatusEnum::PUBLIC && $status !== StatusEnum::PRIVATE){
            $fail('The :attribute must be public or private.');
        }
    }  
}    