<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class ChapterShowRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }
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
            // 'chapters' => 'array',
            //'title' => ['required'],
            'chapter_id' => 'required|integer',
            'course_id' => 'required|integer',
            // 'chapters.*.lesson_id' => 'required|integer',
            // 'chapters.*.title' => 'required|string',
            // 'chapters.*.url' => 'required|string',
            // 'chapters.*.remarks' => 'string',
            // 'chapters.*.order' => 'integer',
        ];
    }
}
