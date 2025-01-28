<?php

namespace App\Rules;

use App\Model\Notification;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotificationUpdateStatusRule implements ValidationRule
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
                    Notification::TYPE_ALWAYS,
                    Notification::TYPE_ONCE,
                ],
                true
            )
        ) {
            // エラーを返す
            $fail('The :attribute must be a valid notification type.');
        }
    }
}
