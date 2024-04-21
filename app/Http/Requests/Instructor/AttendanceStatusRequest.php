<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceStatusRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'attendance_id' => ['required','integer', 'exists:attendances,id,deleted_at,NULL'],
        ];
    }

    /**
     * Get the attendance ID from the request.
     *
     * @return int
     */
    public function attendanceId()
    {
        return $this->route('attendance_id');
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'attendance_id' => $this->route('attendance_id'),
        ]);
    }
}
