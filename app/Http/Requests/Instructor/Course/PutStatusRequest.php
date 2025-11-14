<?php

namespace App\Http\Requests\Instructor\Course;

use App\Rules\CourseStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class PutStatusRequest extends FormRequest
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
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id,deleted_at,NULL'],
            'status' => ['required', 'string', new CourseStatusRule],
        ];
    }
}
