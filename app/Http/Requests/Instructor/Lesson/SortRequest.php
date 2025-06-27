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
            // order値を検証する。
            'lessons.*.order' => ['required', 'integer', 'exists:lessons,id,deleted_at,NULL'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $chapterId = $this->input('chapter_id');
            $inputLessonIds = collect($this->input('lessons'))->pluck('lesson_id')->toArray();

            // 指定された chapter_id に属し、論理削除されていないレッスンを取得
            $validLessonIds = Lesson::where('chapter_id', $chapterId)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            // 対象のレッスンIDがすべて含まれているかチェック
            $diff = array_diff($validLessonIds, $inputLessonIds);

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
