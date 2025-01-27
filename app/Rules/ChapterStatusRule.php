<?php

namespace App\Rules;

use App\Model\Chapter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ChapterStatusRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! in_array(
                $value,
                [
                    Chapter::STATUS_PRIVATE,
                    Chapter::STATUS_PUBLIC,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('Invalid status.');
        }
    }
}
