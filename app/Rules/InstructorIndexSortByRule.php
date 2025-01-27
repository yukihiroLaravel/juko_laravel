<?php

namespace App\Rules;

use App\Model\Instructor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstructorIndexSortByRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, [
            Instructor::SORT_BY_EMAIL,
            Instructor::SORT_BY_NICK_NAME,
            Instructor::SORT_BY_CREATED_AT,
        ], true)) {
            $fail('The :attribute is invalid.');
        }
    }
}
