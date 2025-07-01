<?php

namespace App\Http\Requests\Instructor\Lesson;

use App\Model\Lesson;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

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
     * 最初のバリデーション
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

    // 追加のバリデーション
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $courseId = (int) $this->input('course_id');
                $chapterId = (int) $this->input('chapter_id');
                $inputLessons = $this->input('lessons', []);

                // chapter_id に紐づくレッスンを全て取得
                $allLessonIds = Lesson::with('chapter.course')
                    ->where('chapter_id', $chapterId)->get();

                // inputLessonsのレッスンを取得
                $lessons = $allLessonIds->whereIn('id', $inputLessons);

                // (1) lesson が chapter_id に属しているか
                $invalidChapter = $lessons->reject(fn($lesson) => $lesson->chapter_id === $chapterId);
                if ($invalidChapter->isNotEmpty()) {
                    $validator->errors()->add('lessons', 'invalid lessons found for the specified chapter.');
                }

                // (2) lesson が course_id に属しているか
                $invalidCourse = $lessons->reject(fn($lesson) => $lesson->chapter->course_id === $courseId);
                if ($invalidCourse->isNotEmpty()) {
                    $validator->errors()->add('lessons', 'invalid lessons found for the specified course.');
                }

                // (3) chapter_id に属する lesson がすべて含まれているか
                $allLessonIds = $allLessonIds->pluck('id')->toArray();

                $diff = array_diff($allLessonIds, $inputLessons);
                if (! empty($diff)) {
                    $validator->errors()->add('lessons', 'all valid lessons not found for the specified chapter.');
                }

                // (4) 重複しているlessonsがあるか
                if (count($inputLessons) !== count(array_unique($inputLessons))) {
                    $validator->errors()->add('lessons', 'duplicate lessons found.');
                }
            }
        ];
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
