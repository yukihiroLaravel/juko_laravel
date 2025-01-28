<?php

namespace App\Rules;

use App\Model\Attendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexSortByRule implements ValidationRule
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
                    Attendance::SORT_BY_NICK_NAME,
                    Attendance::SORT_BY_EMAIL,
                    Attendance::SORT_BY_ATTENDANCED_AT,
                    Attendance::SORT_BY_LAST_LOGIN_AT,
                ],
                true
            )
        ) {
            $fail('The :attribute must be a valid attendance period.');
        }
    }
}
