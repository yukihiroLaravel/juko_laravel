<?php

namespace App\Http\Requests\Manager\Lesson;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
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
        $this->merge([
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
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'lessons' => ['required', 'array'],
            'lessons.*' => ['required', 'integer', 'exists:lessons,id,deleted_at,NULL'],
        ];
    }
}
