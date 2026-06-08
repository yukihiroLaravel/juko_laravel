<?php

namespace App\Http\Requests\Instructor\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StuckPointsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
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
