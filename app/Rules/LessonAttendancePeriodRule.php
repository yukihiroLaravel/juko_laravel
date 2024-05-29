<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\LessonAttendance;

class LessonAttendancePeriodRule implements Rule
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
                    LessonAttendance::PERIOD_TODAY,
                    LessonAttendance::PERIOD_MONTH,
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
        return 'Invalid Period.';
    }
}
