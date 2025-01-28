<?php

namespace App\Rules;

use App\Model\Course;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CourseStatusRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !in_array(
                $value,
                [
                    Course::STATUS_PRIVATE,
                    Course::STATUS_PUBLIC,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid attendance period.');
        }
    }
}
