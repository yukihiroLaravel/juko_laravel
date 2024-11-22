<?php

namespace App\Rules;

use App\Model\Attendance;
use Illuminate\Contracts\Validation\Rule;

class AttendancePeriodRule implements Rule
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
                    Attendance::PERIOD_WEEK,
                    Attendance::PERIOD_MONTH,
                    Attendance::PERIOD_YEAR,
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
