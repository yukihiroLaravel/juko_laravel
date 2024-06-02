<?php

namespace App\Http\Requests\Instructor;

use App\Rules\LessonStatusRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'lessons' => 'required|array',
            'lessons.*' => 'required|integer|exists:lessons,id',
            'status' => ['required', 'string', new LessonStatusRule()],
            'course_id' => 'required|integer|exists:courses,id',
            'chapter_id' => 'required|integer|exists:chapters,id',
        ];
    }
}
