<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\LessonStatusRule;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'chapter_id' => $this->route('chapter_id'),
        ]);

        return [
            'lessons' => 'required|array',
            'lessons.*' => 'required|integer|exists:lessons,id',
            'status' => ['required', 'string', new LessonStatusRule()],
            'course_id' => 'required|integer|exists:courses,id',
            'chapter_id' => 'required|integer|exists:chapters,id',
        ];
    }
}
