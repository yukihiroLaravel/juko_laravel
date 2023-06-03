<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class LessonEditRequest extends FormRequest
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
            'lessons' => 'array',
            'lessons.*.lesson_id' => 'required|integer',
            'lessons.*.title' => 'required|string',
            'lessons.*.url' => 'required|string',
            'lessons.*.remark' => 'string',
            'lessons.*.order' => 'integer',
        ];
    }
}