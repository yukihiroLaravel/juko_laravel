<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Course;

class CourseStatusRule implements Rule
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
        if (
            in_array(
                $value,
                [
                Course::STATUS_PRIVATE,
                Course::STATUS_PUBLIC
                ],
                true
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Status.';
    }
}
