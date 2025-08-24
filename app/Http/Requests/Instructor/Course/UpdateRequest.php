<?php

namespace App\Http\Requests\Instructor\Course;

use App\Rules\CourseStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'course_id' => $this->route('course_id'),
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
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'title' => ['required', 'string'],
            'image' => ['mimes:jpg,png'],
            'status' => ['required', 'string', new CourseStatusRule],
            'deadline_type' => ['required', 'in:none,fixed_date,relative_days'],
            'fixed_date' => ['required_if:deadline_type,fixed_date', 'date_format:Y-m-d', 'after_or_equal:today', 'nullable'],
            'relative_days' => ['required_if:deadline_type,relative_days', 'integer', 'min:1', 'nullable'],
        ];
    }
}
