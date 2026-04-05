<?php

namespace App\Http\Requests\Student\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAllChaptersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'attendance_id' => $this->route('attendance_id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            /** @ignoreParam */
            'attendance_id' => ['required', 'integer', 'exists:attendances,id,deleted_at,NULL'],
        ];
    }
}
