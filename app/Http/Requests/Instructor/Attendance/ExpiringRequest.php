<?php

namespace App\Http\Requests\Instructor\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ExpiringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /** @ignoreParam */
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'thresholds' => ['required', 'array', 'min:1'],
            'thresholds.*' => ['integer', 'min:1', 'max:365', 'distinct'],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'thresholds' => is_array($this->thresholds) ? array_map('intval', $this->thresholds) : $this->thresholds,
        ]);
    }
}
