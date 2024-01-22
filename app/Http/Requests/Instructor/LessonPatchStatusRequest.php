<?php

namespace App\Http\Requests\Instructor;

use App\Rules\LessonStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class LessonPatchStatusRequest extends FormRequest
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
            'lesson_id' => $this->route('lesson_id'),
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
            'course_id' => ['required', 'integer'],
            'chapter_id' => ['required', 'integer'],
            'lesson_id' => ['required', 'integer'],
            'status' => ['required', 'string',new LessonStatusRule()],
        ];
    }
}
