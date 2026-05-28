<?php

namespace App\Rules;

use App\Model\Notification;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotificationSortByRule implements ValidationRule
{
    /**
     * バリデーションの実行。
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! in_array(
                $value,
                [
                    Notification::SORT_BY_TITLE,
                    Notification::SORT_BY_COURSE_ID,
                    Notification::SORT_BY_START_DATE,
                    Notification::SORT_BY_INSTRUCTOR_NICK_NAME,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid sort by name.');
        }
    }
}
