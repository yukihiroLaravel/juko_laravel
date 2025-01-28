<?php

namespace App\Rules;

use App\Model\LessonAttendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LessonAttendancePeriodRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! in_array(
                $value,
                [
                    LessonAttendance::PERIOD_TODAY,
                    LessonAttendance::PERIOD_MONTH,
                ],
                true
            )
        ) {
            $fail('The :attribute must be a valid attendance period.');
        }
    }
}
