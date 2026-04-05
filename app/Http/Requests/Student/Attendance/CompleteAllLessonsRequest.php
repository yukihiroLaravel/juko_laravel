<?php

namespace App\Http\Requests\Student\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAllLessonsRequest extends FormRequest
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
            'attendance_id' => $this->route('attendance_id'),
            'chapter_id' => $this->route('chapter_id'),
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
            /** @ignoreParam */
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
        ];
    }
}
