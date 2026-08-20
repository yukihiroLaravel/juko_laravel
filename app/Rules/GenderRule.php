<?php

namespace App\Rules;

use App\Enums\Student\GenderEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GenderRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 入力値が 'man' または 'woman' であることを検証
        $isValid = collect(GenderEnum::cases())
            ->filter(fn (GenderEnum $gender) => $gender !== GenderEnum::UNKNOWN)
            ->contains(fn (GenderEnum $gender) => $gender->value === $value);

        if (! $isValid) {
            $fail('The :attribute must be either '.GenderEnum::MAN->value.' or '.GenderEnum::WOMAN->value.'.');
        }
    }
}
