<?php

namespace App\Rules;

use App\Enums\Course\StatusEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CourseStatusRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !in_array($value, [
                StatusEnum::PUBLIC->value,
                StatusEnum::PRIVATE->value,
            ], true)
        ) {
            // エラーを返す
            $fail('The :attribute must be public or private.');
        }
    }
}
