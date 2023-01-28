<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

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
        if($value === 'in_attendance'){
            return true;
        }else if($value === 'before_attendance'){
            return true;
        }else if($value === 'completed_attendance'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '失敗です';
    }
}
