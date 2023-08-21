<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Student;

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
        return $value === Student::SEX_MAN || $value === Student::SEX_WOMAN;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be either "' . Student::SEX_MAN . '" or "' . Student::SEX_WOMAN . '".';
    }
}
