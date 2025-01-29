<?php

namespace App\Rules;

use App\Model\Attendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AttendancePeriodRule implements ValidationRule
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
                    Attendance::PERIOD_WEEK,
                    Attendance::PERIOD_MONTH,
                    Attendance::PERIOD_YEAR,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid attendance period.');
        }
    }
}
