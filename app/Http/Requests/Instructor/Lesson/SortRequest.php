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
     * 最初のバリデーション
     *
     * @return array
     */
    public function rules()
    {
        return [
            /** @ignoreParam */
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
                $chapterId = (int) $this->input('chapter_id');
                $inputLessons = $this->input('lessons', []);

                $allLessons = Lesson::where(function ($query) use ($chapterId, $inputLessons) {
                    $query->whereIn('id', $inputLessons)
                        ->orWhere('chapter_id', $chapterId);
                })->get();

                // 入力されたレッスンのみ取り出し
                $inputLessonsCollection = $allLessons->whereIn('id', $inputLessons);

                // (1) lesson が chapter_id に属しているか
                $invalidChapter = $inputLessonsCollection->reject(fn ($lesson) => $lesson->chapter_id === $chapterId);
                if ($invalidChapter->isNotEmpty()) {
                    $validator->errors()->add('lessons', 'invalid lessons found for the specified chapter.');
                }

                // (2) chapter_id に属する lesson がすべて含まれているか
                $validLessonIds = $allLessons->where('chapter_id', $chapterId)
                    ->pluck('id')
                    ->toArray();

                $diff = array_diff($validLessonIds, $inputLessons);
                if ($diff !== []) {
                    $validator->errors()->add('lessons', 'all valid lessons not found for the specified chapter.');
                }

                // (3) 重複しているlessonsがあるか
                if (count($inputLessons) !== count(array_unique($inputLessons))) {
                    $validator->errors()->add('lessons', 'duplicate lessons found.');
                }
            },
        ];
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }
}
