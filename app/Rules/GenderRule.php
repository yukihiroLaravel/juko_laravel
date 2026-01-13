<?php

namespace App\Rules;

use App\Enums\Student\Gender;
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
        $isValid = collect(Gender::cases())
            ->filter(fn (Gender $gender) => $gender !== Gender::UNKNOWN)
            ->contains(fn (Gender $gender) => $gender->value === $value);

        if (! $isValid) {
            $fail('The :attribute must be either '.Gender::MAN->value.' or '.Gender::WOMAN->value.'.');
        }
    }
}
