<?php

namespace App\Rules;

use App\Enums\Chapter\StatusEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ChapterStatusRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (StatusEnum::tryFrom($value) === null) {
            $fail('The :attribute must be a valid status.');
        }
    }
}
