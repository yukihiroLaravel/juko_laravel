<?php

namespace App\Http\Requests\Instructor\Lesson;

use App\Model\Chapter;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAllRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $chapter = Chapter::find($this->route('chapter_id'));
        $this->merge([
            'chapter_id' => $this->route('chapter_id'),
            'course_id' => $chapter?->course_id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
        ];
    }
}
