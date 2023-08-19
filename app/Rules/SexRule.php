<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SexRule implements Rule
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
        return $value === 'man' || $value === 'woman';
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
