<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

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
        return in_array($value, ['man', 'woman'], true);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be either "man" or "woman".';
    }
}

