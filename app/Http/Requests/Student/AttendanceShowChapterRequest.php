<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceShowChapterRequest extends FormRequest
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
            'course_id' => $this->route('course_id'),
            'attendance_id' => $this->route('attendance_id'),
            'chapter_id' => $this->route('chapter_id')
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
            'attendance_id' => ['required', 'integer', 'exists:attendances,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
        ];
    }
}
