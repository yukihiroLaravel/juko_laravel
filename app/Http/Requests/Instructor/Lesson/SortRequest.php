<?php

namespace App\Http\Requests\Instructor\Lesson;

use App\Model\Lesson;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SortRequest extends FormRequest
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
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'lessons' => ['required', 'array'],
            'lessons.*' => ['required', 'integer'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $courseId = (int)$this->input('course_id');
            $chapterId = (int)$this->input('chapter_id');
            $inputLessons = $this->input('lessons', []);

            // 取得
            $lessons = Lesson::with('chapter.course')
                ->whereIn('id', $inputLessons)
                ->get();

            // ① lesson が chapter_id に属しているか
            $invalidChapter = $lessons->reject(fn($lesson) => $lesson->chapter_id === $chapterId);
            if ($invalidChapter->isNotEmpty()) {
                $validator->errors()->add('lessons', 'invalid lessons found for the specified chapter.');
            }

            // ② lesson が course_id に属しているか
            $invalidCourse = $lessons->reject(fn($lesson) => $lesson->chapter->course_id === $courseId);
            if ($invalidCourse->isNotEmpty()) {
                $validator->errors()->add('lessons', 'invalid lessons found for the specified course.');
            }

            // ③ chapter_id に属する lesson がすべて含まれているか（既存の検証）
            $validLessonIds = Lesson::where('chapter_id', $chapterId)
                ->pluck('id')
                ->toArray();

            $diff = array_diff($validLessonIds, $inputLessons);
            if (! empty($diff)) {
                $validator->errors()->add('lessons', 'all valid lessons not found for the specified chapter.');
            }
        });
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }
}
