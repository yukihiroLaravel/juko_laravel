<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ChapterStatusRule;

class ChapterPatchStatusRequest extends FormRequest
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
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'status' => ['required', 'string',new ChapterStatusRule()],
        ];
    }
}
