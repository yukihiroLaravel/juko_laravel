<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Attendance;

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
                Attendance::COLUMN_NICK_NAME,
                Attendance::COLUMN_EMAIL,
                Attendance::COLUMN_TITLE,
                Attendance::COLUMN_CREATED_AT,
                Attendance::COLUMN_LAST_LOGIN_AT,
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
        return 'Invalid Colunm Name.';
    }
}
