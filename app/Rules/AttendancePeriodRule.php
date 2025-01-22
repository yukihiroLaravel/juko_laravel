<?php

namespace App\Rules;

use App\Model\Attendance;
use Illuminate\Validation\Rule;

class AttendancePeriodRule implements Rule
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
        return in_array(
            $value,
            [
                Attendance::PERIOD_WEEK,
                Attendance::PERIOD_MONTH,
                Attendance::PERIOD_YEAR,
            ],
            true
        );
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
