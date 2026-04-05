<?php

namespace App\Http\Requests\Instructor\Attendance;

use App\Rules\LessonAttendancePeriodRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowStatusRequest extends FormRequest
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
            /** @ignoreParam */
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            /** @ignoreParam */
            'period' => ['required', 'string', new LessonAttendancePeriodRule],
        ];
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'period' => $this->route('period'),
        ]);
    }
}
