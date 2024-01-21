<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class LessonStoreRequest extends FormRequest
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
        'chapter_id' => $this->route('chapter_id'),
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
        'chapter_id' => ['required','integer','exists:chapters,id'],
        'course_id' => ['required','integer','exists:courses,id'],
        'title' => ['required','string', 'max:50'],
        ];
    }
}
