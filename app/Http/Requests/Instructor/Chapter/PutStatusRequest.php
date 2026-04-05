<?php

namespace App\Http\Requests\Instructor\Chapter;

use App\Rules\ChapterStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class PutStatusRequest extends FormRequest
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
            /** @ignoreParam */
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'status' => ['required', 'string', new ChapterStatusRule],
        ];
    }
}
