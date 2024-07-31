<?php

namespace App\Rules;

use App\Model\Instructor;
use Illuminate\Contracts\Validation\Rule;

class InstructorIndexSortByRule implements Rule
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

    protected $validSortByFields = [
        Instructor::SORT_BY_EMAIL,
        Instructor::SORT_BY_NICK_NAME,
        Instructor::SORT_BY_CREATED_AT,
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return in_array($value, $this->validSortByFields, true);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
