<?php

namespace App\Rules;

use App\Model\LessonAttendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LessonAttendancePeriodRule implements ValidationRule
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
                    LessonAttendance::PERIOD_TODAY,
                    LessonAttendance::PERIOD_MONTH,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid attendance period.');
        }
    }
}
