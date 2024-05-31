<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Lesson;

class LessonStatusRule implements Rule
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
        return in_array($value, [
            Lesson::STATUS_PRIVATE,
            Lesson::STATUS_PUBLIC
        ], true);
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
