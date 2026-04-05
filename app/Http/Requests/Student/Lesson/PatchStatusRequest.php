<?php

namespace App\Http\Requests\Student\Lesson;

use App\Rules\LessonAttendanceStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class PatchStatusRequest extends FormRequest
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

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'lesson_attendance_id' => $this->route('lesson_attendance_id'),
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
            'lesson_attendance_id' => ['required', 'integer', 'exists:lesson_attendances,id,deleted_at,NULL'],
            'status' => ['required', new LessonAttendanceStatusRule],
        ];
    }
}
