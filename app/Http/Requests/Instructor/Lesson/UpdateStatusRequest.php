<?php

namespace App\Http\Requests\Instructor\Lesson;

use App\Rules\LessonStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
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
            'lesson_id' => $this->route('lesson_id'),
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
            'lesson_id' => ['required', 'integer', 'exists:lessons,id,deleted_at,NULL'],
            'status' => ['required', 'string', new LessonStatusRule],
        ];
    }
}
