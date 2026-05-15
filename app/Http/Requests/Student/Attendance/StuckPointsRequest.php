<?php

namespace App\Http\Requests\Student\Attendance;

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

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'attendance_id' => $this->route('attendance_id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'integer', 'exists:attendances,id,deleted_at,NULL'],
        ];
    }
}
