<?php

namespace App\Http\Requests\Instructor\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FollowUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /** @ignoreParam */
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'days' => ['required', 'integer', 'min:1'],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
        ]);
    }
}
