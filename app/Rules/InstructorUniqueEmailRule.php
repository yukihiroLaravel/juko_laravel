<?php

namespace App\Rules;

use App\Model\Instructor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstructorUniqueEmailRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        private readonly ?string $email
    ) {}

    /**
     * バリデーションの実行。
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->email && $value === $this->email) {
            return;
        }

        if (Instructor::where('email', $value)->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}
