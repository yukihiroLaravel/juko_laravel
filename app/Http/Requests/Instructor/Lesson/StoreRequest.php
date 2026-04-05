<?php

namespace App\Http\Requests\Instructor\Lesson;

use App\Model\Chapter;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $chapter = Chapter::find($this->route('chapter_id'));
        $this->merge([
            'chapter_id' => $this->route('chapter_id'),
            'course_id' => $chapter?->course_id,
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
            /** @ignoreParam */
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            /** @ignoreParam */
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'title' => ['required', 'string', 'max:50'],
        ];
    }
}
