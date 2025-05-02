<?php

namespace App\Rules;

use App\Model\Lesson;
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
        if (
            ! in_array(
                $value,
                [
                    Lesson::STATUS_PRIVATE,
                    Lesson::STATUS_PUBLIC,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid status.');
        }
    }
}
