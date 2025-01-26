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
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
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
            $fail('Invalid Period.');
        }
    }
}
