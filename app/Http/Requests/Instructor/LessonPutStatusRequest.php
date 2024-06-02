<?php

namespace App\Http\Requests\Instructor;

use App\Rules\LessonStatusRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonPutStatusRequest extends FormRequest
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
            'lessons' => ['required', 'array'],
            'lessons.*' => ['required', 'integer', Rule::exists('lessons', 'id')->whereNull('deleted_at')],
            'status' => ['required', 'string', new LessonStatusRule()],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->whereNull('deleted_at')],
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')->whereNull('deleted_at')],
        ];
    }
}
