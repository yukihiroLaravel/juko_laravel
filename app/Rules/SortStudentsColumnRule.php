<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Attendance;

class SortStudentsColumnRule implements Rule
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
                Attendance::NICK_NAME_COLUMN,
                Attendance::EMAIL_COLUMN,
                Attendance::TITLE_COLUMN,
                Attendance::CREATED_AT_COLUMN,
                Attendance::LAST_LOGIN_AT_COLUMN,
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
