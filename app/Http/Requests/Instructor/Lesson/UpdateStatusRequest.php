<?php

namespace App\Http\Requests\Instructor\Lesson;

use App\Model\Lesson;
use App\Rules\LessonStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
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

    #[\Override]
    protected function prepareForValidation()
    {
        $lesson = Lesson::with('chapter')->find($this->route('lesson_id'));
        $this->merge([
            'lesson_id' => $this->route('lesson_id'),
            'chapter_id' => $lesson?->chapter_id,
            'course_id' => $lesson?->chapter?->course_id,
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
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'lesson_id' => ['required', 'integer', 'exists:lessons,id,deleted_at,NULL'],
            'status' => ['required', 'string', new LessonStatusRule],
        ];
    }
}
