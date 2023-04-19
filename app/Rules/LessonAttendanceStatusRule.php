<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\LessonAttendance;

class LessonAttendanceStatusRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

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
                LessonAttendance::STATUS_IN_ATTENDANCE,
                LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
                LessonAttendance::STATUS_BEFORE_ATTENDANCE
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
