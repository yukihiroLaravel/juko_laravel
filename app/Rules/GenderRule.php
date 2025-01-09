<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Enums\Student\Gender;

class GenderRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // 入力値が 'man' または 'woman' であることを検証
        return collect(Gender::cases())
            ->filter(fn (Gender $gender) => $gender !== Gender::UNKNOWN)
            ->contains(fn (Gender $gender) => $gender->label() === $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be either ' . Gender::MAN->label() . ' or ' . Gender::WOMAN->label() . '.';
    }
}
