<?php

namespace App\Http\Requests\Instructor;

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
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
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
            'chapters' => ['required', 'array'],
            'chapters.*.chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'chapters.*.order' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
        ];
    }
}
