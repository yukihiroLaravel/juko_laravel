<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Student;

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
        return $value === Student::GENDER_MAN || $value === Student::GENDER_WOMAN;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be either ' . Student::GENDER_MAN . ' or ' . Student::GENDER_WOMAN . '.';
    }
}
