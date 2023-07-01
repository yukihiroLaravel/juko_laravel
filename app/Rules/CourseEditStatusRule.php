<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Course;

class CourseEditStatusRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

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
                COURSE::STATUS_PRIVATE,
                COURSE::STATUS_PUBLIC
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
        return 'The validation error message.';
    }
}
