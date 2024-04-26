<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Attendance;
use App\Model\Notification;
use App\Model\Course;

class IndexSortByRule implements Rule
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
                Attendance::SORT_BY_NICK_NAME,
                Attendance::SORT_BY_EMAIL,
                Attendance::SORT_BY_ATTENDANCED_AT,
                Attendance::SORT_BY_LAST_LOGIN_AT,
                Notification::SORT_BY_TITLE,
                Notification::SORT_BY_COURSE_ID,
                Notification::SORT_BY_START_DATE,
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
        return 'Invalid Sort By Name.';
    }
}
