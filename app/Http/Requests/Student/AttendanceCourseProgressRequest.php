<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCourseProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'attendance_id' => $this->route('attendance_id')
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
            'attendance_id' => ['required', 'integer', 'exists:attendances,id']
        ];
    }
}
