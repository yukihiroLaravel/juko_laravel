<?php

namespace App\Rules;

use App\Model\Instructor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstructorTypeRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! in_array(
                $value,
                [
                    Instructor::TYPE_MANAGER,
                    Instructor::TYPE_INSTRUCTOR,
                ],
                true
            )
        ) {
            $fail('The :attribute must be a valid instructor type.');
        }
    }
}
