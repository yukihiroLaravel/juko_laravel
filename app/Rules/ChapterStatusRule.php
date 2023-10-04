<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Model\Chapter;

class ChapterStatusRule implements Rule
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
                Chapter::STATUS_PRIVATE,
                Chapter::STATUS_PUBLIC
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
        return 'Invalid status.';
    }
}
