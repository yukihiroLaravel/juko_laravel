<?php

namespace App\Rules;

use App\Model\LessonAttendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LessonAttendanceStatusRule implements ValidationRule
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
                    LessonAttendance::STATUS_IN_ATTENDANCE,
                    LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
                    LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid status.');
        }
    }
}
