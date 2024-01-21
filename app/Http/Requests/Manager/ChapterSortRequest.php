<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class ChapterSortRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // ここで認証は行わないのでtrueを返す
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
            'chapters' => ['required', 'array'],
            'chapters.*.chapter_id' => ['required', 'integer'],
            'chapters.*.order' => ['required', 'integer'],
        ];
    }

    protected function prepareForValidation()
    {
        // 講座IDはリクエストのURLから取得
        $this->merge([
            'course_id' => $this->route('course_id'),
        ]);
    }
}
