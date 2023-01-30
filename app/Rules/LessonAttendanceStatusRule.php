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
        $values = array($value);
        if (in_array(LessonAttendance::STATUS_IN_ATTENDANCE, $values, true)) {
            return true;
        }
        if (in_array(LessonAttendance::STATUS_COMPLETED_ATTENDANCE, $values, true)) {
            return true;
        }
        if (in_array(LessonAttendance::STATUS_BEFORE_ATTENDANCE, $values, true)) {
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
        return 'Invalid Request Body.';
    }
}
