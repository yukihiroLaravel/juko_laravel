<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class LessonSortRequest extends FormRequest
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
            'course_id' => ['required', 'integer'],
            'chapter_id' => ['required', 'integer'],
            'lessons' => ['required', 'array'],
            'lessons.*.lesson_id' => ['required', 'integer'],
            'lessons.*.order' => ['required', 'integer'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }
}
