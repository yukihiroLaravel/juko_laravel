<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAllLessonsRequest extends FormRequest
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
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'lesson_status' => ['required', 'in:active,inactive'], // 例として追加のルール
        ];
    }
}
