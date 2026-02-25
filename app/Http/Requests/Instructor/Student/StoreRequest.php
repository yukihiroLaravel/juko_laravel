<?php

namespace App\Http\Requests\Instructor\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'given_name_by_instructor' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:students'],
            'course_id' => ['required', Rule::exists('courses', 'id')->whereNull('deleted_at'),],
        ];
    }
}
