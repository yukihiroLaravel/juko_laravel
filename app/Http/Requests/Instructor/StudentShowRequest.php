<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StudentShowRequest extends FormRequest
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
            'student_id' => ['required','integer', 'exists:students,id,deleted_at,NULL'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'student_id' => $this->route('student_id'),
        ]);
    }
}
