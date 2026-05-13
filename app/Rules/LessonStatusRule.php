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
        if (StatusEnum::tryFrom($value) === null) {
            // エラーを返す
            $fail('The :attribute must be a valid status.');
        }
    }
}
