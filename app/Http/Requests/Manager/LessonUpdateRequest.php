<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\LessonUpdateStatusRule;

class LessonUpdateRequest extends FormRequest
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
            'lesson_id' => $this->route('lesson_id'),
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
            'lesson_id' => ['required','integer', 'exists:lessons,id,deleted_at,NULL'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'title' => ['required','string','max:50'],
            'url' => ['required','string'],
            'remarks' => ['nullable','string'],
            'status' => ['required', 'string', new LessonUpdateStatusRule()],
        ];
    }
}
