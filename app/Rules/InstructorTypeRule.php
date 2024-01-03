<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Instructor;

class InstructorTypeRule implements Rule
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
                Instructor::TYPE_MANAGER,
                Instructor::TYPE_INSTRUCTOR
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
        return 'Invalid Type.';
    }
}
